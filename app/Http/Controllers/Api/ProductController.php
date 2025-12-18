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
        try {
            $branchId = $request->get('branch_id');

            if (!$branchId) {
                return response()->json(['error' => 'Branch ID is required'], 400);
            }

            // Buscar producto
            $product = Product::where('code', $code)->first();
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            // Buscar relación product_branch
            $branch = $product->productBranches()
                ->where('branch_id', $branchId)
                ->first();

            if (!$branch) {
                return response()->json(['error' => 'Product not found in this branch'], 404);
            }

            // Obtener precio de venta (ProductBranchPrice)
            $priceModel = $product->salePriceModel($branchId);

            // Precio numérico (float)
            $salePrice = $priceModel?->amount ?? 0;

            // Precio formateado (ej: "$ 1.234,00")
            $formattedPrice = $priceModel?->getFormattedAmount() ?? '$ 0,00';

            // Construimos un "view model" para el Blade
            $viewProduct = (object)[
                'id'         => $product->id,
                'code'       => $product->code,
                'name'       => $product->name,
                'stock'      => $branch->stock,
                'sale_price' => $salePrice,
                'sale_price_formatted' => $formattedPrice,
            ];

            return response()->json([
                'product' => $viewProduct,
                'html'    => view('admin.order.partials._item_row', [
                    'product' => $viewProduct
                ])->render(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista de productos filtrada por branch
     */
    public function list(Request $request)
    {
        $branchId = $request->get('branch_id');

        if (!$branchId) {
            return response()->json(['error' => 'Branch ID is required'], 400);
        }

        // Cargar ProductBranch + precios
        $products = Product::with(['productBranches' => function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->with('prices');
        }])->get();

        $response = $products->map(function ($product) use ($branchId) {

            $branch = $product->productBranches->first();
            if (!$branch) return null;

            // Obtener precio de venta
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
