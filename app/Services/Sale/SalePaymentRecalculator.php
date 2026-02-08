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
        $isPaid = ($paidARS >= ($targetARS - 2.0));
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
        if (!empty($totals)) {
            $sumARS = 0;
            foreach ($totals as $currencyId => $amount) {
                $val = (float) $amount;
                // Si el total guardado es USD (2), lo llevamos a ARS para comparar contra los pagos
                if ((int)$currencyId === CurrencyType::USD->value) {
                    // Usamos round para que 163503.9 sea 163504 o lo que el usuario espera ver
                    $sumARS += round($val * $rate, 2);
                } else {
                    $sumARS += $val;
                }
            }
            return $sumARS;
        }

        // Si no hay totals, usamos la suma de items directamente en ARS
        return round((float)$sale->items()->sum('subtotal') - (float)$sale->discount_amount, 2);
    }
}
