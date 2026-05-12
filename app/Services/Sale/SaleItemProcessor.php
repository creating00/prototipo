<?php

namespace App\Services\Sale;

use App\Services\BaseItemProcessor;
use App\Models\Sale;
use App\Models\Product;
use App\Services\PriceAuditService;
use App\Services\Product\ProductStockService;
use Illuminate\Database\Eloquent\Model;

class SaleItemProcessor extends BaseItemProcessor
{
    protected PriceAuditService $auditService;

    public function __construct(
        ProductStockService $stockService, // Requerido por el padre
        PriceAuditService $auditService    // Requerido por esta clase
    ) {
        // Inicializa el stockService en la clase Base
        parent::__construct($stockService);

        $this->auditService = $auditService;
    }
    /**
     * @param Sale $model
     */
    protected function createItem(
        Model $model,
        Product $product,
        int $quantity,
        float $unitPrice,
        float $subtotal,
        array $rawItem
    ): void {

        //dd($model);
        $originalPrice = $this->getProductPrice($product, $model);

        $this->auditService->recordModification([
            'branch_id'      => $model->branch_id,
            'user_id'        => $model->user_id,
            'product_id'     => $product->id,
            'original_price' => $originalPrice,
            'modified_price' => $unitPrice,
            'sale_id'        => $model->id,
            'reason'         => 'Precio modificado manualmente en venta'
        ]);

        $model->items()->create([
            'product_id' => $product->id,
            'quantity'   => $quantity,
            'unit_price' => $unitPrice,
            'subtotal'   => $subtotal,
            'currency'   => $rawItem['currency'],
        ]);
    }

    protected function validateStock(Product $product, int $branchId, int $quantity): void
    {
        $branchStock = $product->getStock($branchId);

        if ($branchStock < $quantity) {
            throw new \Exception("No hay suficiente stock para el producto {$product->name}. Disponible: {$branchStock}");
        }
    }

    protected function getProductPrice(Product $product, Model $model): float
    {
        // Determinamos el precio según el tipo de venta
        $unitPrice = match ($model->sale_type) {
            \App\Enums\SaleType::Repair => $product->repairPrice($model->branch_id),
            \App\Enums\SaleType::Sale   => $product->salePrice($model->branch_id),
            default                     => null,
        };

        if (!$unitPrice) {
            $typeName = $model->sale_type->label();
            throw new \Exception("No se encontró un precio de {$typeName} para {$product->name} en la sucursal {$model->branch_id}");
        }

        return (float) $unitPrice;
    }
}
