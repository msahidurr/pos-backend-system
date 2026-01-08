<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dailySales(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        
        $from = $request->query('from') ?? now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?? now()->toDateString();

        $sales = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(subtotal) as total_revenue'),
            DB::raw('SUM(tax_amount) as total_tax'),
            DB::raw('SUM(discount_amount) as total_discount'),
            DB::raw('SUM(total_amount) as net_revenue')
        )
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'data' => $sales,
            'total_revenue' => $sales->sum('net_revenue'),
            'total_orders' => $sales->sum('order_count'),
        ]);
    }

    public function topProducts(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $limit = $request->query('limit', 10);
        $from = $request->query('from') ?? now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?? now()->toDateString();

        $products = DB::table('order_items')
            ->select(
                'products.id',
                'products.sku',
                'products.name',
                'products.selling_price',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.line_total) as total_revenue')
            )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->where('orders.status', 'completed')
            ->whereDate('orders.created_at', '>=', $from)
            ->whereDate('orders.created_at', '<=', $to)
            ->groupBy('products.id', 'products.sku', 'products.name', 'products.selling_price')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'data' => $products,
        ]);
    }

    public function inventorySummary(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');

        $inventory = Product::select(
            DB::raw('COUNT(*) as total_products'),
            DB::raw('SUM(quantity_on_hand) as total_units'),
            DB::raw('SUM(quantity_on_hand * cost_price) as total_cost_value'),
            DB::raw('SUM(quantity_on_hand * selling_price) as total_selling_value'),
            DB::raw('SUM(CASE WHEN quantity_on_hand <= reorder_level THEN 1 ELSE 0 END) as low_stock_count')
        )
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        return response()->json([
            'summary' => $inventory,
            'low_stock_items' => Product::where('tenant_id', $tenantId)
                ->whereRaw('quantity_on_hand <= reorder_level')
                ->count(),
        ]);
    }

    public function salesByPaymentMethod(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $from = $request->query('from') ?? now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?? now()->toDateString();

        $sales = Order::select(
            'payment_method',
            DB::raw('COUNT(*) as order_count'),
            DB::raw('SUM(total_amount) as total_amount')
        )
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'data' => $sales,
        ]);
    }
}
