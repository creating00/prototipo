<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseProductController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends BaseProductController
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'branchId' => 'nullable|integer',
            'categoryId' => 'nullable|integer',
        ]);

        $branchId = $validated['branchId'] ?? null; // obtiene ?branchId=1
        $categoryId = $validated['categoryId'] ?? null;

        return response()->json(
            $this->productService->getAllForSummary($branchId, $categoryId)
        );
    }

    private function resolvePriceModel(Product $product, string $branchId, string $context, bool $isRepair)
    {
        return match ($context) {
            'sale' => $isRepair
                ? ($product->repairPriceModel($branchId) ?? $product->salePriceModel($branchId))
                : $product->salePriceModel($branchId),
            'order' => $product->purchasePriceModel($branchId),
            default => $product->salePriceModel($branchId),
        };
    }

    /**
     * Buscar producto por código y branch (API para órdenes)
     */
    public function findByCode(string $code, Request $request)
    {
        $branchId   = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $isRepair   = $request->boolean('is_repair');
        $context    = $request->get('context', 'order');

        if (!$branchId) {
            return response()->json(['error' => 'Branch ID is required'], 400);
        }

        $product = Product::where('code', $code)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->first();

        if (!$product || !$product->branchContext($branchId)) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Buscamos el modelo de precio según contexto
        $priceEntry = $this->resolvePriceModel($product, $branchId, $context, $isRepair);

        // Si no existe precio, devolvemos 0 y moneda por defecto (ARS)
        $finalPrice = $priceEntry?->amount ?? 0;


        $currency = $priceEntry?->currency ?? \App\Enums\CurrencyType::ARS;

        return response()->json([
            'product' => [
                'id'         => $product->id,
                'code'       => $product->code,
                'name'       => $product->name,
                'stock'      => $product->getStock($branchId),
                'sale_price' => $finalPrice,
                'currency'   => [
                    'code'   => $currency->code(),
                    'symbol' => $currency->symbol(),
                ],
            ],
            'html' => view('admin.order.partials._item_row', [
                'product'        => $product,
                'stock'          => $product->getStock($branchId),
                'salePrice'      => $finalPrice,
                'currency'       => $currency,
                'item'           => null,
                'allowEditPrice' => ($context === 'sale'),
            ])->render(),
        ]);
    }

    /**
     * Lista de productos filtrada por branch y opcionalmente por categoría
     */
    public function list(Request $request)
    {
        $branchId   = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $search     = $request->get('q');
        $isRepair   = $request->boolean('is_repair');
        $context    = $request->get('context', 'sale');

        if (!$branchId) {
            return response()->json(['error' => 'Branch ID is required'], 400);
        }

        $query = Product::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query
            ->whereHas('productBranches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->available();
            })
            ->limit(15)
            ->get();

        $response = $products->map(function ($product) use ($branchId, $context, $isRepair) {
            // Usamos la lógica centralizada
            $priceEntry = $this->resolvePriceModel($product, $branchId, $context, $isRepair);

            return [
                'id'            => $product->id,
                'code'          => $product->code,
                'name'          => $product->name,
                'stock'         => $product->getStock($branchId),
                'price'         => $priceEntry?->amount ?? 0,
                'price_display' => $priceEntry?->getFormattedAmount() ?? '$ 0,00',
            ];
        });

        return response()->json($response->values());
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
