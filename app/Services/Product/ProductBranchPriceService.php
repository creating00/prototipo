<?php

namespace App\Services\Product;

use App\Models\ProductBranch;
use App\Enums\PriceType;

class ProductBranchPriceService
{
    public function createPricesForBranch(ProductBranch $branch, array $data): void
    {
        // Precio de Compra
        $branch->prices()->create([
            'type'     => PriceType::PURCHASE,
            // Usamos los campos específicos del componente
            'currency' => $data['purchase_price_currency'],
            'amount'   => $data['purchase_price_amount'],
        ]);

        // Precio de Venta
        $branch->prices()->create([
            'type'     => PriceType::SALE,
            // Usamos los campos específicos del componente
            'currency' => $data['sale_price_currency'],
            'amount'   => $data['sale_price_amount'],
        ]);

        // Precio Mayorista (Opcional)
        // Usamos el campo de monto para verificar si se envió
        if (!empty($data['wholesale_price_amount'])) {
            $branch->prices()->create([
                'type'     => PriceType::WHOLESALE,
                // Usamos los campos específicos del componente
                'currency' => $data['wholesale_price_currency'],
                'amount'   => $data['wholesale_price_amount'],
            ]);
        }
    }

    public function updatePricesForBranch(ProductBranch $branch, array $data): void
    {
        // Compra
        $this->upsertPrice(
            $branch,
            PriceType::PURCHASE,
            $data['purchase_price_currency'],
            $data['purchase_price_amount']
        );

        // Venta
        $this->upsertPrice(
            $branch,
            PriceType::SALE,
            $data['sale_price_currency'],
            $data['sale_price_amount']
        );

        // Mayorista (opcional)
        if (!empty($data['wholesale_price_amount'])) {
            $this->upsertPrice(
                $branch,
                PriceType::WHOLESALE,
                $data['wholesale_price_currency'],
                $data['wholesale_price_amount']
            );
        }
    }

    private function upsertPrice(ProductBranch $branch, PriceType $type, int $currency, float $amount): void
    {
        $price = $branch->prices()
            ->where('type', $type->value)
            ->where('currency', $currency)
            ->first();

        if ($price) {
            $price->update(['amount' => $amount]);
        } else {
            $branch->prices()->create([
                'type'     => $type->value,
                'currency' => $currency,
                'amount'   => $amount,
            ]);
        }
    }
}
