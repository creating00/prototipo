<?php

namespace App\Traits;

trait CalculatesSubtotalFromItems
{
    protected function calculateSubtotalFromItems(array $items): float
    {
        return array_reduce($items, function ($carry, $item) {
            return $carry
                + ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
        }, 0);
    }
}
