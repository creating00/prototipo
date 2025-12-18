<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductBranchPrice;
use App\Enums\PriceType;
use App\Enums\CurrencyType;
use App\Traits\AuthTrait;

class ProductPriceService
{
    use AuthTrait;
    public function branchContext(Product $product, ?int $branchId = null): ?ProductBranch
    {
        $branchId = $branchId ?? $this->currentBranchId();

        return $product->productBranches()
            ->where('branch_id', $branchId)
            ->first();
    }

    /**
     * Obtiene el modelo de precio según branch, tipo y opcionalmente moneda.
     */
    public function getPriceModel(
        Product $product,
        ?int $branchId,
        PriceType $type,
        ?CurrencyType $currency = null
    ): ?ProductBranchPrice {

        $branch = $this->branchContext($product, $branchId);

        if (!$branch) {
            return null;
        }

        $query = $branch->prices()->where('type', $type->value);

        if ($currency) {
            $query->where('currency', $currency->value);
        }

        return $query->first();
    }

    /**
     * Devuelve el valor numérico del precio.
     * Permite fallback de moneda: intenta en una moneda, y si no existe, intenta en otra.
     */
    public function getPriceValue(
        Product $product,
        ?int $branchId,
        PriceType $type,
        CurrencyType $currency,
        bool $fallback = true
    ): ?float {

        $model = $this->getPriceModel($product, $branchId, $type, $currency);

        if ($model) {
            return $model->amount;
        }

        // Fallback automático a ARS si no existe USD, por ejemplo
        if ($fallback) {
            foreach (CurrencyType::cases() as $alt) {
                if ($alt === $currency) {
                    continue;
                }

                $altModel = $this->getPriceModel($product, $branchId, $type, $alt);
                if ($altModel) {
                    return $altModel->amount;
                }
            }
        }

        return null;
    }

    /**
     * Devuelve el precio ya formateado con el símbolo de moneda.
     */
    public function getFormattedPrice(
        Product $product,
        ?int $branchId,
        PriceType $type,
        CurrencyType $currency,
        string $class = 'fw-bold text-success'
    ): string {

        $value = $this->getPriceValue($product, $branchId, $type, $currency);

        if ($value === null) {
            return '<span class="text-muted">N/A</span>';
        }

        return sprintf(
            '<span class="%s">%s %s</span>',
            $class,
            $currency->symbol(),
            number_format($value, 0, ',', '.')
        );
    }
}
