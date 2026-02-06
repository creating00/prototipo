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
        // Usamos la nueva función para determinar el objetivo de pago
        $targetARS = $this->resolveTargetAmount($sale, $totals, $isDollarSale, $rate);

        // 2. Normalizar Pagos a Pesos
        $paidARS = $sale->payments->reduce(function ($carry, $payment) use ($rate) {
            $amount = (float) $payment->amount;
            if ($payment->currency === CurrencyType::USD) {
                return $carry + ($amount * ($payment->exchange_rate ?? $rate));
            }
            return $carry + $amount;
        }, 0);

        // 3. Cálculo de estados y saldos
        $isPaid = ($paidARS >= ($targetARS - 0.10));
        $diffARS = round($paidARS - $targetARS, 2);

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

    /**
     * Determina el monto objetivo a pagar.
     * Centralizado para fácil reversión o cambio de lógica.
     */
    protected function resolveTargetAmount(Sale $sale, array $totals, bool $isDollarSale, float $rate): float
    {
        // PRIORIDAD: Si el array 'totals' tiene valores, usamos eso.
        // Esto respeta la normalización de "salida rápida" que se hizo en el Trait.
        if (!empty($totals)) {
            $totalInArray = (float) array_sum($totals);
            return $isDollarSale ? round($totalInArray * $rate, 2) : round($totalInArray, 2);
        }

        // FALLBACK: Lógica original basada en items (si totals estuviera vacío)
        return round((float)$sale->items()->sum('subtotal') - (float)$sale->discount_amount, 2);
    }
}
