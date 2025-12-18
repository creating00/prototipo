<?php

namespace App\Traits;

trait CalculatesTotalFromItems
{
    protected function calculateTotalFromItems(array $items): float
    {
        return array_reduce($items, function ($carry, $item) {
            $quantity = $item['quantity'] ?? 0;
            $unitPrice = $item['unit_price'] ?? 0;
            $discount = $item['discount'] ?? 0;

            $subtotal = $quantity * $unitPrice;
            $discountAmount = $subtotal * ($discount / 100);

            return $carry + ($subtotal - $discountAmount);
        }, 0);
    }
}
