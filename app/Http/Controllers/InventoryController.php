<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryTransaction;
use App\Http\Requests\AdjustInventoryRequest;
use App\Http\Resources\InventoryTransactionResource;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function adjustStock(AdjustInventoryRequest $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $product = Product::where('id', $request->product_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        try {
            return DB::transaction(function () use ($product, $request, $tenantId) {
                $newQuantity = $product->quantity_on_hand + $request->adjustment_quantity;

                if ($newQuantity < 0) {
                    return response()->json([
                        'error' => 'Adjustment would result in negative stock'
                    ], 422);
                }

                $product->update(['quantity_on_hand' => $newQuantity]);

                $transaction = $product->inventoryTransactions()->create([
                    'tenant_id' => $tenantId,
                    'type' => 'adjustment',
                    'quantity' => $request->adjustment_quantity,
                    'notes' => $request->reason ?? null,
                    'created_by' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'Stock adjusted successfully',
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity_on_hand' => $product->quantity_on_hand,
                    ],
                    'transaction' => new InventoryTransactionResource($transaction->load('product', 'createdBy')),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function transactions(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');

        $transactions = InventoryTransaction::with('product', 'createdBy')
            ->where('tenant_id', $tenantId)
            ->when($request->query('product_id'), function ($q) use ($request) {
                $q->where('product_id', $request->query('product_id'));
            })
            ->when($request->query('type'), function ($q) use ($request) {
                $q->where('type', $request->query('type'));
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return InventoryTransactionResource::collection($transactions);
    }

    public function lowStockAlerts(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');

        $products = Product::where('tenant_id', $tenantId)
            ->whereRaw('quantity_on_hand <= reorder_level')
            ->where('status', 'active')
            ->orderBy('quantity_on_hand')
            ->get();

        return response()->json([
            'total_low_stock_items' => $products->count(),
            'items' => ProductResource::collection($products),
        ]);
    }
}
