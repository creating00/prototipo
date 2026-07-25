<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseProductController;
use App\Models\Product;
use App\Enums\CategoryTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ProductController extends BaseProductController
{
    /**
     * Display a listing of products filtered by request parameters.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'branchId'   => 'nullable|integer',
            'categoryId' => 'nullable|integer',
            'target'     => [
                'nullable',
                'integer',
                Rule::enum(CategoryTarget::class)
            ],
        ]);

        return response()->json(
            $this->productService->getAllForSummary(
                $validated['branchId'] ?? null,
                $validated['categoryId'] ?? null,
                $validated['target'] ?? null
            )
        );
    }

    private function resolvePriceModel(Product $product, ?string $branchId, string $context, bool $isRepair)
    {
        $price = match ($context) {
            'sale' => $isRepair
                ? ($product->repairPriceModel($branchId) ?? $product->salePriceModel($branchId))
                : $product->salePriceModel($branchId),
            'order' => $product->purchasePriceModel($branchId) ?? $product->salePriceModel($branchId),
            default => $product->salePriceModel($branchId),
        };

        return $price ?? $product->salePriceModel(null) ?? $product->purchasePriceModel(null);
    }

    /**
     * Buscar producto por código y branch (API para órdenes)
     */
    public function findByCode(Request $request)
    {
        $code = $request->get('code');
        $branchId   = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $isRepair   = $request->boolean('is_repair');
        $context    = $request->get('context', 'order');

        if (!$branchId) {
            $branchId = auth()->user()?->branch_id;
        }

        $product = Product::where('code', $code)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->first();

        if (!$product) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Buscamos el modelo de precio según contexto con fallback
        $priceEntry = $this->resolvePriceModel($product, $branchId, $context, $isRepair);

        $finalPrice = $priceEntry?->amount ?? 0;
        $currency = $priceEntry?->currency ?? \App\Enums\CurrencyType::ARS;
        $stock = $branchId ? $product->getStock($branchId) : 0;

        return response()->json([
            'product' => [
                'id'         => $product->id,
                'code'       => $product->code,
                'name'       => $product->name,
                'stock'      => $stock,
                'sale_price' => $finalPrice,
                'currency'   => [
                    'code'   => $currency->code(),
                    'symbol' => $currency->symbol(),
                ],
            ],
            'html' => view('admin.order.partials._item_row', [
                'product'        => $product,
                'stock'          => $stock,
                'salePrice'      => $finalPrice,
                'currency'       => $currency,
                'item'           => null,
                'allowEditPrice' => ($context === 'saleX'),
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
            $branchId = auth()->user()?->branch_id;
        }

        $query = Product::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->limit(20)->get();

        $response = $products->map(function ($product) use ($branchId, $context, $isRepair) {
            $priceEntry = $this->resolvePriceModel($product, $branchId, $context, $isRepair);

            return [
                'id'            => $product->id,
                'code'          => $product->code,
                'name'          => $product->name,
                'stock'         => $branchId ? $product->getStock($branchId) : 0,
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
            $data = $request->except(['removeImage']);

            if ($request->hasFile('imageFile')) {
                $data['imageFile'] = $request->file('imageFile');
            }

            if (empty($data['branch_id'])) {
                $data['branch_id'] = auth()->user()?->branch_id ?? \App\Models\Branch::first()?->id;
            }

            if (!isset($data['stock']) || $data['stock'] === '') {
                $data['stock'] = 0;
            }

            if (empty($data['status'])) {
                $data['status'] = \App\Enums\ProductStatus::Available->value;
            }

            if (!isset($data['purchase_price_amount']) || $data['purchase_price_amount'] === '') {
                $data['purchase_price_amount'] = 0;
            }
            if (empty($data['purchase_price_currency'])) {
                $data['purchase_price_currency'] = 1;
            }

            if (!isset($data['sale_price_amount']) || $data['sale_price_amount'] === '') {
                $data['sale_price_amount'] = 0;
            }
            if (empty($data['sale_price_currency'])) {
                $data['sale_price_currency'] = 1;
            }

            $product = $this->productService->create(
                data: $data,
                imageFile: $request->file('imageFile'),
                imageUrl: $request->input('imageUrl')
            );

            $branchId = (int)$data['branch_id'];

            return response()->json([
                'id'    => $product->id,
                'code'  => $product->code,
                'name'  => $product->name,
                'stock' => $branchId ? $product->getStock($branchId) : 0,
                'price' => $branchId ? $product->salePrice($branchId) : 0,
            ], 201);
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
                $request->except(['imageFile', 'imageUrl', 'removeImage']),
                $request->file('imageFile'),
                $request->get('imageUrl'),
                $request->boolean('removeImage')
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
            $this->productService->delete($product, 1);

            return response()->json(['message' => 'Product deleted'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
