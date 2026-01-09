<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');

        $orders = Order::with('items.product', 'createdBy')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), function ($q) use ($request) {
                $q->where('status', $request->query('status'));
            })
            ->when($request->query('from_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->query('from_date'));
            })
            ->when($request->query('to_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->query('to_date'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        $tenantId = $request->header('X-Tenant-ID');

        try {
            return DB::transaction(function () use ($request, $tenantId) {
                $validated = $request->validated();

                // Verify all products exist and have sufficient stock
                foreach ($validated['items'] as $item) {
                    $product = Product::where('id', $item['product_id'])
                        ->where('tenant_id', $tenantId)
                        ->first();

                    if (!$product) {
                        return response()->json(['error' => 'Product not found'], 404);
                    }

                    if ($product->quantity_on_hand < $item['quantity']) {
                        return response()->json([
                            'error' => "Insufficient stock for {$product->name}. Available: {$product->quantity_on_hand}"
                        ], 422);
                    }
                }

                // Create order
                $order = Order::create([
                    'tenant_id' => $tenantId,
                    'order_no' => Order::generateOrderNo($tenantId),
                    'customer_name' => $validated['customer_name'],
                    'customer_contact' => $validated['customer_contact'] ?? null,
                    'discount_amount' => $validated['discount_amount'] ?? 0,
                    'tax_amount' => $validated['tax_amount'] ?? 0,
                    'payment_method' => $validated['payment_method'],
                    'payment_received' => $validated['payment_received'] ?? false,
                    'created_by' => auth()->id(),
                ]);

                $subtotal = 0;

                // Create order items and update inventory
                foreach ($validated['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    $lineTotal = $product->selling_price * $item['quantity'];

                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->selling_price,
                        'line_total' => $lineTotal,
                    ]);

                    // Update product stock
                    $product->decrement('quantity_on_hand', $item['quantity']);

                    // Record inventory transaction
                    $product->inventoryTransactions()->create([
                        'tenant_id' => $tenantId,
                        'type' => 'sale',
                        'quantity' => -$item['quantity'],
                        'reference_no' => $order->order_no,
                        'created_by' => auth()->id(),
                    ]);

                    $subtotal += $lineTotal;
                }

                // Calculate totals
                $order->update([
                    'subtotal' => $subtotal,
                    'total_amount' => $subtotal - ($validated['discount_amount'] ?? 0) + ($validated['tax_amount'] ?? 0),
                    'status' => 'pending',
                ]);

                return new OrderResource($order->load('items.product', 'createdBy'));
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, Order $order)
    {
        $tenantId = $request->header('X-Tenant-ID');

        if ($order->tenant_id != $tenantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return new OrderResource($order->load('items.product', 'createdBy'));
    }

    public function cancel(Request $request, Order $order)
    {
        $tenantId = $request->header('X-Tenant-ID');

        if ($order->tenant_id != $tenantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json(['error' => 'Only pending orders can be cancelled'], 422);
        }

        $order->update(['status' => 'cancelled']);

        return new OrderResource($order);
    }
}
