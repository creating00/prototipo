<?php

namespace App\Services\Sale;

use App\Enums\SaleType;
use App\Traits\CalculatesSubtotalFromItems;

class SaleTotalResolver
{
    use CalculatesSubtotalFromItems;

    public function resolve(array $data): float
    {
        // 1. Prioridad: Si es una reparación, el total es el monto de reparación
        if (
            isset($data['sale_type']) &&
            (int) $data['sale_type'] === SaleType::Repair->value
        ) {
            return round((float) ($data['repair_amount'] ?? 0), 2);
        }

        // 2. Calcular Subtotal base
        $subtotal = $this->calculateSubtotalFromItems($data['items'] ?? []);

        // 3. Calcular Descuento (si existe)
        $discountAmount = 0;
        if (!empty($data['discount_id'])) {
            $discount = \App\Models\Discount::active()->find($data['discount_id']);
            if ($discount) {
                $discountAmount = $discount->calculateAmount($subtotal);
            }
        }

        // 4. Calcular Total Final
        $finalTotal = max(0, $subtotal - $discountAmount);

        return round($finalTotal, 2);
    }
}
