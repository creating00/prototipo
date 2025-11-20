<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::with(['branch', 'category', 'ratings'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'           => 'required|string|unique:products',
            'name'           => 'required|string',
            'image'          => 'nullable|string',
            'description'    => 'nullable|string',
            'stock'          => 'integer|min:0',
            'branch_id'      => 'required|exists:branches,id',
            'category_id'    => 'nullable|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
        ]);

        return Product::create($validated)
            ->load(['branch', 'category', 'ratings']);
    }

    public function show(Product $product)
    {
        return $product->load(['branch', 'category', 'ratings']);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code'           => 'required|string|unique:products,code,' . $product->id,
            'name'           => 'required|string',
            'image'          => 'nullable|string',
            'description'    => 'nullable|string',
            'stock'          => 'integer|min:0',
            'branch_id'      => 'required|exists:branches,id',
            'category_id'    => 'nullable|exists:categories,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
        ]);

        $reservedStock = Order::where('product_id', $product->id)
            ->sum('quantity');

        if (isset($validated['stock']) && $validated['stock'] < $reservedStock) {
            return response()->json([
                'error' => 'Cannot reduce stock below reserved quantity (' . $reservedStock . ')'
            ], 400);
        }

        $product->update($validated);

        return $product->load(['branch', 'category', 'ratings']);
    }

    public function destroy(Product $product)
    {
        $hasOrders = Order::where('product_id', $product->id)->count();

        if ($hasOrders > 0) {
            return response()->json([
                'error' => 'Cannot delete a product with active orders'
            ], 400);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
