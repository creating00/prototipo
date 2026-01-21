<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Services\Product\ProductStockService;

abstract class BaseItemProcessor
{
    protected ProductStockService $stockService;

    public function __construct(ProductStockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Procesa y sincroniza los items del modelo
     * 
     * @param Model $model Modelo que contiene los items (Order o Sale)
     * @param array $items Array de items a procesar
     * @return float Total calculado
     */
    final public function sync(Model $model, array $items, bool $skipStockMovement = false): array
    {
        $totals = [];
        $branchId = $model->branch_id;

        // Obtenemos ítems actuales indexados por product_id
        $existingItems = $model->items()->get()->keyBy('product_id');

        // Extraemos IDs de productos que vienen en el nuevo request
        $incomingProductIds = collect($items)->pluck('product_id')->toArray();

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            $currency = $item['currency'] ?? \App\Enums\CurrencyType::ARS->value;

            $product = $this->getLockedProduct($productId);

            if (!$skipStockMovement) {
                $this->validateStock($product, $branchId, $quantity);
                $this->stockService->reserve($product, $quantity, $branchId);
            }

            $unitPrice = $item['unit_price'] ?? $this->getProductPrice($product, $branchId);
            $subtotal = $unitPrice * $quantity;

            $totals[$currency] = ($totals[$currency] ?? 0) + $subtotal;

            if ($existingItems->has($productId)) {
                // Actualización de ítem existente
                $existingItems[$productId]->update([
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'currency' => $currency,
                ]);
            } else {
                // Creación de nuevo ítem
                $this->createItem($model, $product, $quantity, $unitPrice, $subtotal, $item);
            }
        }

        // ELIMINACIÓN: Solo borrar los que NO están en el request actual
        $model->items()->whereNotIn('product_id', $incomingProductIds)->delete();

        return $totals;
    }

    /**
     * Libera el stock reservado por los items del modelo
     * 
     * @param Model $model Modelo cuyos items se liberarán
     */
    final public function releaseStock(Model $model): void
    {
        $branchId = $model->branch_id;

        foreach ($model->items as $item) {
            $product = $this->getLockedProduct($item->product_id);
            $this->stockService->release($product, $item->quantity, $branchId);
        }
    }

    /**
     * Crea un nuevo item para el modelo
     * 
     * @param Model $model Modelo padre
     * @param Product $product Producto
     * @param int $quantity Cantidad
     * @param float $unitPrice Precio unitario
     * @param float $subtotal Subtotal
     */
    abstract protected function createItem(
        Model $model,
        Product $product,
        int $quantity,
        float $unitPrice,
        float $subtotal,
        array $rawItem
    ): void;

    /**
     * Valida el stock disponible para un producto
     * 
     * @param Product $product Producto a validar
     * @param int $branchId ID de la sucursal
     * @param int $quantity Cantidad requerida
     * @throws \Exception Si no hay suficiente stock
     */
    abstract protected function validateStock(Product $product, int $branchId, int $quantity): void;

    /**
     * Obtiene el precio de venta de un producto en una sucursal
     * 
     * @param Product $product Producto
     * @param int $branchId ID de la sucursal
     * @return float Precio de venta
     * @throws \Exception Si no se encuentra el precio
     */
    abstract protected function getProductPrice(Product $product, int $branchId): float;

    /**
     * Obtiene un producto bloqueado para escritura
     * 
     * @param int $productId ID del producto
     * @return Product Producto bloqueado
     */
    final protected function getLockedProduct(int $productId): Product
    {
        return Product::where('id', $productId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
