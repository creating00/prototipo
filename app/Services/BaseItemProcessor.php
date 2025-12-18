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
    final public function sync(Model $model, array $items): float
    {
        $total = 0;
        $branchId = $model->branch_id;

        foreach ($items as $item) {
            $product = $this->getLockedProduct($item['product_id']);
            $this->validateStock($product, $branchId, $item['quantity']);

            $unitPrice = $this->getProductPrice($product, $branchId);
            $this->stockService->reserve($product, $item['quantity'], $branchId);

            $subtotal = $unitPrice * $item['quantity'];
            $total += $subtotal;

            $this->createItem($model, $product, $item['quantity'], $unitPrice, $subtotal);
        }

        return $total;
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
        float $subtotal
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
