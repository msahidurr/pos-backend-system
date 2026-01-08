<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        
        $products = Product::where('tenant_id', $tenantId)
            ->when($request->query('status'), function ($q) use ($request) {
                $q->where('status', $request->query('status'));
            })
            ->when($request->query('low_stock'), function ($q) {
                $q->whereRaw('quantity_on_hand <= reorder_level');
            })
            ->when($request->query('search'), function ($q) use ($request) {
                $search = $request->query('search');
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $tenantId = $request->header('X-Tenant-ID');
        
        $product = Product::create(array_merge($request->validated(), [
            'tenant_id' => $tenantId,
        ]));

        return new ProductResource($product);
    }

    public function show(Request $request, Product $product)
    {
        $tenantId = $request->header('X-Tenant-ID');
        
        if ($product->tenant_id != $tenantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $tenantId = $request->header('X-Tenant-ID');
        
        if ($product->tenant_id != $tenantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $product->update($request->validated());

        return new ProductResource($product);
    }

    public function destroy(Request $request, Product $product)
    {
        $tenantId = $request->header('X-Tenant-ID');
        
        if ($product->tenant_id != $tenantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if product has been used in orders
        if ($product->orderItems()->exists()) {
            return response()->json([
                'error' => 'Cannot delete product that has been used in orders'
            ], 422);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
