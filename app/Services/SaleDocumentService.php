<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Payment;
use App\Enums\CurrencyType;
use App\Enums\SaleType;
use App\Enums\RepairType;

class SaleDocumentService
{
    public static function buildItemsFromSale(
        Sale $sale,
        ?Payment $payment = null
    ): array {
        $exchangeRate = $sale->exchange_rate ?? 1;
        $currency = $payment?->currency ?? CurrencyType::ARS;

        return $sale->items->map(function ($item) use ($sale, $currency, $exchangeRate) {
            $price = $currency === CurrencyType::USD
                ? $item->unit_price / $exchangeRate
                : $item->unit_price;

            return [
                'description' => self::resolveItemDescription($sale, $item),
                'quantity'    => $item->quantity,
                'price'       => $price,
                'total'       => $price * $item->quantity,
            ];
        })->toArray();
    }

    public static function buildTotals(
        Sale $sale,
        ?Payment $payment = null,
        array $items = []
    ): array {
        $exchangeRate = $sale->exchange_rate ?? 1;
        $currency = $payment?->currency ?? CurrencyType::ARS;

        $subtotal = $currency === CurrencyType::USD
            ? array_sum(array_column($items, 'total'))
            : $sale->getTotalInCurrency(CurrencyType::ARS, $exchangeRate);

        return [
            'subtotal'      => $subtotal,
            'total'         => $subtotal,
            'currency_code' => $currency->code(),
            'amount_paid'   => $payment?->amount,
            'exchange_rate' => $exchangeRate,
        ];
    }

    private static function resolveItemDescription($sale, $item): string
    {
        $description = $item->product->name;

        if ($sale->sale_type === SaleType::Repair) {
            $description = '[' . self::getRepairLabel($item->product->category_id) . '] ' . $description;
        }

        return $description;
    }

    private static function getRepairLabel(?int $categoryId): string
    {
        if (!$categoryId) return 'Reparación';

        foreach (RepairType::cases() as $repair) {
            if ($repair->categoryId() === $categoryId) {
                return $repair->label();
            }
        }

        return 'Reparación General';
    }
}
