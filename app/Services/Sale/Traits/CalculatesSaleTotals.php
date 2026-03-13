<?php

namespace App\Services\Sale\Traits;

use App\Enums\CurrencyType;

trait CalculatesSaleTotals
{
    protected function calculateNormalizedData(array $data, array $totals): array
    {
        $rate = (float) ($data['exchange_rate_blue'] ?? 1);
        $isDollarSale = isset($totals[CurrencyType::USD->value]);

        // 1. Obtener el total nominal de la venta (si es USD, será el monto en USD)
        $totalVentaNominal = array_sum(array_map('floatval', $totals));

        // 2. Sumar lo recibido (usando los nombres de tus inputs de Blade)
        $amount1 = (float) ($data['amount_received'] ?? 0);
        $amount2 = (float) ($data['amount_received_2'] ?? 0);
        $totalReceivedNominal = $amount1 + $amount2;

        // 3. Calcular vuelto y balance en la moneda original de la venta
        $changeNominal = max(0, $totalReceivedNominal - $totalVentaNominal);
        $balanceNominal = max(0, $totalVentaNominal - $totalReceivedNominal);

        // 4. Normalizar a PESOS para la base de datos
        // Guardamos el vuelto y el balance convertidos a ARS para consistencia en reportes
        if ($isDollarSale) {
            $normalizedChange = $changeNominal * $rate;
            $normalizedBalance = $balanceNominal * $rate;
        } else {
            $normalizedChange = $changeNominal;
            $normalizedBalance = $balanceNominal;
        }

        return [
            'exchange_rate'     => $rate,
            'amount_received'   => $totalReceivedNominal, // Se guarda el monto tal cual se recibió
            'change_returned'   => $normalizedChange,
            'remaining_balance' => $normalizedBalance,
            'is_dollar_sale'    => $isDollarSale
        ];
    }
}
