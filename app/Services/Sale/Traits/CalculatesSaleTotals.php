<?php

namespace App\Services\Sale\Traits;

use App\Enums\CurrencyType;

trait CalculatesSaleTotals
{
    /**
     * Calcula los montos normalizados, vuelto y balance para la venta.
     */
    protected function calculateNormalizedData(array $data, array $totals): array
    {
        $rate = (float) ($data['exchange_rate_blue'] ?? 1);
        $isDollarSale = isset($totals[CurrencyType::USD->value]);

        // 1. Venta total convertida a PESOS
        $totalVentaEnPesos = 0;
        foreach ($totals as $currencyId => $amount) {
            if ((int)$currencyId === CurrencyType::USD->value) {
                $totalVentaEnPesos += ($amount * $rate);
            } else {
                $totalVentaEnPesos += $amount;
            }
        }

        // 2. Total recibido (ARS) considerando dualidad
        $amountReceived = (float) ($data['amount_received'] ?? 0);
        if (isset($data['enable_dual_payment']) && (int)$data['enable_dual_payment'] === 1) {
            $amountReceived += (float) ($data['amount_received_2'] ?? 0);
        }

        // 3. Vuelto real
        $realChange = max(0, $amountReceived - $totalVentaEnPesos);

        // 4. Balance normalizado a pesos
        $rawBalance = (float) ($data['remaining_balance'] ?? 0);
        $normalizedBalance = $isDollarSale ? ($rawBalance * $rate) : $rawBalance;

        return [
            'exchange_rate'     => $rate,
            'amount_received'   => $amountReceived,
            'change_returned'   => $realChange,
            'remaining_balance' => $normalizedBalance,
            'is_dollar_sale'    => $isDollarSale
        ];
    }
}
