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
        return Product::with('branch')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'           => 'required|unique:products',
            'name'           => 'required',
            'description'    => 'nullable',
            'stock'          => 'integer|min:0',
            'branch_id'      => 'required|exists:branches,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
        ]);

        return Product::create($validated);
    }

    public function show(Product $product)
    {
        return $product->load('branch');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code'           => 'required|unique:products,code,' . $product->id,
            'name'           => 'required',
            'description'    => 'nullable',
            'stock'          => 'integer|min:0',
            'branch_id'      => 'required|exists:branches,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
        ]);

        $stockComprometido = Order::where('product_id', $product->id)
            ->sum('quantity');

        if (isset($validated['stock']) && $validated['stock'] < $stockComprometido) {
            return response()->json([
                'error' => 'Cannot reduce stock below reserved quantity (' . $stockComprometido . ')'
            ], 400);
        }

        $product->update($validated);

        return $product->load('branch');
    }

    public function destroy(Product $product)
    {
        $pedidosAsociados = Order::where('product_id', $product->id)->count();

        if ($pedidosAsociados > 0) {
            return response()->json([
                'error' => 'Cannot delete a product with active orders'
            ], 400);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
