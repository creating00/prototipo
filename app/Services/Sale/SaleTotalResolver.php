<?php

namespace App\Services\Sale;

use App\Enums\SaleType;
use App\Traits\CalculatesTotalFromItems;

class SaleTotalResolver
{
    use CalculatesTotalFromItems;

    /**
     * Resuelve el total real de la venta según su tipo.
     */
    public function resolve(array $data): float
    {
        if (
            isset($data['sale_type']) &&
            (int) $data['sale_type'] === SaleType::Repair->value
        ) {
            return (float) ($data['repair_amount'] ?? 0);
        }

        return $this->calculateTotalFromItems($data['items'] ?? []);
    }
}
