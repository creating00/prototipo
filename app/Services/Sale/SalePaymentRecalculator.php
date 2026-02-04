<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Enums\SaleStatus;
use App\Enums\CurrencyType;

class SalePaymentRecalculator
{
    public function recalculate(Sale $sale): void
    {
        $sale->refresh();

        $totals = $sale->totals ?? [];
        $isDollarSale = isset($totals[CurrencyType::USD->value]);
        $rate = (float) ($sale->exchange_rate ?? 1);

        // 1. Normalizar Target a Pesos
        // Si la venta es en USD, el target para el vuelto debe ser USD * Rate
        $targetUSD = (float) ($totals[CurrencyType::USD->value] ?? 0);
        $targetARS = $isDollarSale
            ? round($targetUSD * $rate, 2)
            : round((float)$sale->items()->sum('subtotal') - (float)$sale->discount_amount, 2);

        // 2. Normalizar Pagos a Pesos
        // Sumamos los pagos convirtiendo los que sean USD a ARS
        $paidARS = $sale->payments->reduce(function ($carry, $payment) use ($rate) {
            $amount = (float) $payment->amount;
            if ($payment->currency === CurrencyType::USD) {
                return $carry + ($amount * ($payment->exchange_rate ?? $rate));
            }
            return $carry + $amount;
        }, 0);

        // 3. Cálculo de estados y saldos sobre moneda base (ARS)
        $isPaid = ($paidARS >= ($targetARS - 0.10)); // Margen de 10 centavos
        $diffARS = round($paidARS - $targetARS, 2);

        // 4. Preparar datos de actualización
        $remaining = (!$isPaid) ? max(0, abs($diffARS)) : 0;
        $change = ($isPaid && $targetARS > 0) ? max(0, $diffARS) : 0;

        $updateData = [
            'amount_received'   => $paidARS,
            'change_returned'   => $change,
            'remaining_balance' => $remaining,
            'status'            => ($isPaid && $targetARS > 0) ? SaleStatus::Paid->value : SaleStatus::Pending->value,
        ];

        $sale->updateQuietly($updateData);
    }
}
