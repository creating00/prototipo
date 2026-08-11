<?php

namespace App\Services\Product;

use App\Models\ProductBranch;
use App\Enums\PriceType;

class ProductBranchPriceService
{
    public function createPricesForBranch(ProductBranch $branch, array $data): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $isProvincialAdmin = $user && $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);

        // Precio de Compra
        $branch->prices()->create([
            'type'     => PriceType::PURCHASE,
            'currency' => $data['purchase_price_currency'] ?? 1,
            'amount'   => $isProvincialAdmin ? ($data['purchase_price_amount'] ?? 0) : 0,
        ]);

        // Precio de Venta
        $branch->prices()->create([
            'type'     => PriceType::SALE,
            'currency' => $data['sale_price_currency'] ?? 1,
            'amount'   => $isProvincialAdmin ? ($data['sale_price_amount'] ?? 0) : 0,
        ]);

        // Precio Mayorista (Opcional)
        if ($isProvincialAdmin && !empty($data['wholesale_price_amount'])) {
            $branch->prices()->create([
                'type'     => PriceType::WHOLESALE,
                'currency' => $data['wholesale_price_currency'],
                'amount'   => $data['wholesale_price_amount'],
            ]);
        }

        // Precio de Reparación (Opcional)
        if ($isProvincialAdmin && !empty($data['repair_price_amount'])) {
            $branch->prices()->create([
                'type'     => PriceType::REPAIR,
                'currency' => $data['repair_price_currency'],
                'amount'   => $data['repair_price_amount'],
            ]);
        }
    }

    public function updatePricesForBranch(ProductBranch $branch, array $data): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user || !$user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value)) {
            return;
        }

        // Compra
        if (isset($data['purchase_price_currency'], $data['purchase_price_amount'])) {
            $this->upsertPrice($branch, PriceType::PURCHASE, (int)$data['purchase_price_currency'], (float)$data['purchase_price_amount']);
        }

        // Venta
        if (isset($data['sale_price_currency'], $data['sale_price_amount'])) {
            $this->upsertPrice($branch, PriceType::SALE, (int)$data['sale_price_currency'], (float)$data['sale_price_amount']);
        }

        // Mayorista (opcional)
        if (isset($data['wholesale_price_currency'], $data['wholesale_price_amount'])) {
            $this->upsertPrice($branch, PriceType::WHOLESALE, (int)$data['wholesale_price_currency'], (float)$data['wholesale_price_amount']);
        }

        // Reparación (opcional)
        if (isset($data['repair_price_currency'], $data['repair_price_amount'])) {
            $this->upsertPrice($branch, PriceType::REPAIR, (int)$data['repair_price_currency'], (float)$data['repair_price_amount']);
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
