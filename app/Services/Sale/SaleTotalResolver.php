<?php

namespace App\Services\Sale;

use App\Enums\SaleType;
use App\Traits\CalculatesSubtotalFromItems;

class SaleTotalResolver
{
    use CalculatesSubtotalFromItems;

    public function resolve(array $data): float
    {
        // 1. Reparaciones
        $saleType = isset($data['sale_type']) ? (int) $data['sale_type'] : 0;
        if ($saleType === SaleType::Repair->value) {
            return round((float) ($data['repair_amount'] ?? 0), 2);
        }

        // 2. Calcular Subtotal
        $items = $data['items'] ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            // Buscamos unit_price o price para ser compatibles
            $price = $item['unit_price'] ?? $item['price'] ?? 0;
            $qty = $item['quantity'] ?? $item['qty'] ?? 0;
            $subtotal += ($price * $qty);
        }

        // 3. Descuento
        $discountAmount = 0;
        if (!empty($data['discount_id'])) {
            $discount = \App\Models\Discount::active()->find($data['discount_id']);
            if ($discount) {
                $discountAmount = $discount->calculateAmount($subtotal);
            }
        }

        return round(max(0, $subtotal - $discountAmount), 2);
    }
}
