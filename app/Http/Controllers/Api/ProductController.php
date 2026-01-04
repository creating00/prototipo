<?php

namespace App\Http\Controllers\Api;

use App\Enums\CurrencyType;
use App\Http\Controllers\BaseProductController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends BaseProductController
{
    public function index(Request $request)
    {
        $branchId = $request->query('branchId'); // obtiene ?branchId=1
        return response()->json(
            $this->productService->getAllForSummary($branchId)
        );
    }

    /**
     * Buscar producto por código y branch (API para órdenes)
     */
    public function findByCode(string $code, Request $request)
    {
        $branchId   = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $allowEditPrice = $request->get('context') === 'sale';

        if (!$branchId) {
            return response()->json(['error' => 'Branch ID is required'], 400);
        }

        $productQuery = Product::where('code', $code);

        if ($categoryId) {
            $productQuery->where('category_id', $categoryId);
        }

        $product = $productQuery->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Verifica contexto de sucursal
        if (!$product->branchContext($branchId)) {
            return response()->json(['error' => 'Product not found in this branch'], 404);
        }



        return response()->json([
            'product' => [
                'id'         => $product->id,
                'code'       => $product->code,
                'name'       => $product->name,
                'stock'      => $product->getStock($branchId),
                'sale_price' => $product->salePrice($branchId),
            ],
            'html' => view('admin.order.partials._item_row', [
                'product'    => $product,
                'stock'      => $product->getStock($branchId),
                'salePrice'  => $product->salePrice($branchId),
                'item'       => null,
                'allowEditPrice' => $allowEditPrice,
            ])->render(),
        ]);
    }

    /**
     * Lista de productos filtrada por branch y opcionalmente por categoría
     */
    public function list(Request $request)
    {
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');

        if (!$branchId) {
            return response()->json(['error' => 'Branch ID is required'], 400);
        }

        $query = Product::query();

        // Filtro por categoría si viene en la petición
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->with(['productBranches' => function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)->with('prices');
        }])->get();

        $response = $products->map(function ($product) use ($branchId) {
            $branch = $product->productBranches->first();
            if (!$branch) return null;

            $price = $branch->prices
                ->where('type', \App\Enums\PriceType::SALE)
                ->first();

            return [
                'id'            => $product->id,
                'code'          => $product->code,
                'name'          => $product->name,
                'stock'         => $branch->stock,
                'price'         => $price?->amount ?? 0,
                'price_display' => $price?->getFormattedAmount() ?? '$ 0,00',
            ];
        })->filter()->values();

        return response()->json($response);
    }

    /**
     * Crear un producto (API)
     */
    public function store(Request $request)
    {
        try {
            $product = $this->productService->create(
                $request->except('image'),
                $request->file('image'),
                $request->get('image_url')
            );

            return response()->json(
                $product->load(['branches', 'category']),
                201
            );
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar producto
     */
    public function show(Product $product)
    {
        return response()->json(
            $product->load(['branches', 'category'])
        );
    }

    /**
     * Actualizar un producto
     */
    public function update(Request $request, Product $product)
    {
        try {
            $updatedProduct = $this->productService->update(
                $product,
                $request->except('image'),
                $request->file('image'),
                $request->get('image_url')
            );

            return response()->json(
                $updatedProduct->load(['branches', 'category'])
            );
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un producto
     */
    public function destroy(Product $product)
    {
        try {
            $this->productService->delete($product);

            return response()->json(['message' => 'Product deleted'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
