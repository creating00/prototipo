<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoryTarget;
use App\Http\Controllers\BaseProductController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends BaseProductController
{
    /**
     * Display a listing of products filtered by request parameters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'branchId' => 'nullable|integer',
            'categoryId' => 'nullable|integer',
            'target' => [
                'nullable',
                'integer',
                Rule::enum(CategoryTarget::class),
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

    private function resolvePriceModel(Product $product, ?string $branchId, string $context, bool $isRepair, ?string $customerType = null)
    {
        if ($context === 'order') {
            if ($customerType === 'App\Models\Branch') {
                return $product->purchasePriceModel($branchId) ?? $product->purchasePriceModel(null);
            }

            return $product->salePriceModel($branchId) ?? $product->salePriceModel(null);
        }

        $price = match ($context) {
            'sale' => $isRepair
                ? ($product->repairPriceModel($branchId) ?? $product->salePriceModel($branchId))
                : $product->salePriceModel($branchId),
            default => $product->salePriceModel($branchId),
        };

        return $price ?? $product->salePriceModel(null) ?? $product->purchasePriceModel(null);
    }

    /**
     * Buscar producto por código y branch (API para órdenes)
     */
    public function findByCode(Request $request)
    {
        $isMultiDestination = $request->boolean('is_multi_destination');
        if ($isMultiDestination) {
            $this->authorize((int) $request->get('order_source') === \App\Enums\OrderSource::Manual->value ? 'createClient' : 'createBranch', \App\Models\Order::class);
        }
        $code = $request->get('code');
        $branchIdRaw = $request->get('branch_id') ?: auth()->user()?->branch_id;
        $branchId = $branchIdRaw ? (int) $branchIdRaw : null;
        if ($isMultiDestination) {
            $this->validateTransferOrigin($branchId);
        }
        $categoryId = $request->get('category_id');
        $isRepair = $request->boolean('is_repair');
        $context = $request->get('context', 'order');
        $customerType = $request->get('customer_type');

        $product = Product::where('code', $code)
            ->when($isMultiDestination, fn ($q) => $q->whereHas('productBranches', fn ($branches) => $branches->where('branch_id', $branchId)))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->first();

        if (! $product) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Buscamos el modelo de precio según contexto con fallback
        $priceEntry = $this->resolvePriceModel($product, $branchId, $context, $isRepair, $customerType);

        $finalPrice = $priceEntry?->amount ?? 0;
        $currency = $priceEntry?->currency ?? \App\Enums\CurrencyType::ARS;
        $stock = $branchId ? $product->getStock($branchId) : 0;

        $user = auth()->user();
        $canViewCost = $user?->hasAnyRole([\App\Enums\RoleLabel::ADMIN->value, \App\Enums\RoleLabel::PROVINCIAL_ADMIN->value]) ?? false;
        $costModel = $canViewCost ? ($product->purchasePriceModel($branchId) ?? $product->purchasePriceModel(null)) : null;
        $costDisplay = $costModel?->getFormattedAmount();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'stock' => $stock,
                'sale_price' => $finalPrice,
                'purchase_price' => $costModel?->amount,
                'cost_display' => $costDisplay,
                'show_cost' => $canViewCost,
                'currency' => [
                    'code' => $currency->code(),
                    'symbol' => $currency->symbol(),
                ],
            ],
            'html' => view('admin.order.partials._item_row', [
                'product' => $product,
                'stock' => $stock,
                'salePrice' => $finalPrice,
                'currency' => $currency,
                'costDisplay' => $costDisplay,
                'canViewCost' => $canViewCost,
                'showCostCell' => ($canViewCost && $context !== 'order'),
                'context' => $context,
                'branchId' => $branchId,
                'item' => null,
                'allowEditPrice' => true,
                'isMultiDestination' => $isMultiDestination,
            ])->render(),
        ]);
    }

    /**
     * Lista de productos filtrada por branch y opcionalmente por categoría
     */
    public function list(Request $request)
    {
        $isMultiDestination = $request->boolean('is_multi_destination');
        if ($isMultiDestination) {
            $this->authorize((int) $request->get('order_source') === \App\Enums\OrderSource::Manual->value ? 'createClient' : 'createBranch', \App\Models\Order::class);
        }
        $branchIdRaw = $request->get('branch_id') ?: auth()->user()?->branch_id;
        $branchId = $branchIdRaw ? (int) $branchIdRaw : null;
        if ($isMultiDestination) {
            $this->validateTransferOrigin($branchId);
        }
        $categoryId = $request->get('category_id');
        $search = $request->get('q');
        $isRepair = $request->boolean('is_repair');
        $context = $request->get('context', 'sale');
        $customerType = $request->get('customer_type');

        $query = Product::with(['category', 'productBranches.prices']);
        if ($isMultiDestination) {
            $query->whereHas('productBranches', fn ($branches) => $branches->where('branch_id', $branchId));
        }

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

        $user = auth()->user();
        $canViewCost = $user?->hasAnyRole([\App\Enums\RoleLabel::ADMIN->value, \App\Enums\RoleLabel::PROVINCIAL_ADMIN->value]) ?? false;

        $response = $products->map(function ($product) use ($branchId, $isRepair, $canViewCost) {
            // For the autocomplete dropdown, we ALWAYS want to show the sale price (or repair price) in the green badge,
            // even if the context is 'order' (which uses cost for branch orders).
            $priceEntry = $this->resolvePriceModel($product, $branchId, 'sale', $isRepair);
            $costEntry = $canViewCost ? ($product->purchasePriceModel($branchId) ?? $product->purchasePriceModel(null)) : null;
            $branch = $branchId ? $product->branchContext($branchId) : null;
            $status = $branch?->status;

            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category?->name,
                'stock' => $branch?->stock ?? 0,
                'low_stock_threshold' => $branch?->low_stock_threshold,
                'status' => $status?->value,
                'status_label' => $status?->label(),
                'price' => $priceEntry?->amount ?? 0,
                'price_display' => $priceEntry?->getFormattedAmount() ?? '$ 0,00',
                'price_currency' => $priceEntry?->currency?->code(),
                'cost' => $costEntry?->amount,
                'cost_display' => $costEntry?->getFormattedAmount(),
                'cost_currency' => $costEntry?->currency?->code(),
                'show_cost' => $canViewCost,
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

            if (! isset($data['stock']) || $data['stock'] === '') {
                $data['stock'] = 0;
            }

            if (empty($data['status'])) {
                $data['status'] = \App\Enums\ProductStatus::Available->value;
            }

            if (! isset($data['purchase_price_amount']) || $data['purchase_price_amount'] === '') {
                $data['purchase_price_amount'] = 0;
            }
            if (empty($data['purchase_price_currency'])) {
                $data['purchase_price_currency'] = 1;
            }

            if (! isset($data['sale_price_amount']) || $data['sale_price_amount'] === '') {
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

            $branchId = (int) $data['branch_id'];

            return response()->json([
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'stock' => $branchId ? $product->getStock($branchId) : 0,
                'price' => $branchId ? $product->salePrice($branchId) : 0,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation Failed',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function validateTransferOrigin(?int $branchId): void
    {
        $user = auth()->user();
        $activeId = session('active_branch_id', $user?->branch_id);
        $origin = \App\Models\Branch::find($branchId);
        $accessible = $user ? app(\App\Services\BranchService::class)->getAccessibleBranchesForUser($user) : collect();
        if (! $origin || $activeId === 'all' || (int) $activeId !== $branchId || ! $accessible->contains('id', $branchId)) {
            throw ValidationException::withMessages(['branch_id' => 'La búsqueda debe utilizar la sucursal de origen activa y autorizada.']);
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
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
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
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
