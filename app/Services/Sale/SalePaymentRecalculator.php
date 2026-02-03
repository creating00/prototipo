<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Enums\SaleStatus;

class SalePaymentRecalculator
{
    /**
     * Recalcula campos de pago y estado.
     * Los items están en moneda base (ARS), los pagos pueden ser nominales.
     */
    public function recalculate(Sale $sale): void
    {
        $sale->refresh();

        // 1. Determinar Totales y Pagos según moneda
        $totals = $sale->totals ?? [];
        $isDollarSale = isset($totals[2]);
        $rate = (float) ($sale->exchange_rate ?? request('exchange_rate_blue', 1));

        // El "Target" es lo que el cliente debe pagar
        $target = $isDollarSale
            ? (float) ($totals[2] ?? 0)
            : round((float)$sale->items()->sum('subtotal') - (float)$sale->discount_amount, 2);

        // El "Paid" es lo que entró (Nominal)
        $paid = (float) $sale->payments()->sum('amount');

        // 2. Cálculo de estados y saldos
        $isPaid = ($paid >= ($target - 0.01));
        $diff = round($paid - $target, 2);

        // 3. Preparar datos de actualización
        // Si es dólar, el balance pendiente se guarda en pesos para la contabilidad
        $remaining = (!$isPaid)
            ? ($isDollarSale ? round(abs($diff) * $rate, 2) : abs($diff))
            : 0;

        $updateData = [
            'amount_received'   => max((float)$sale->amount_received, $paid),
            'change_returned'   => ($isPaid && $target > 0) ? max(0, $diff) : 0,
            'remaining_balance' => $remaining,
            'status'            => ($isPaid && $target > 0) ? SaleStatus::Paid->value : SaleStatus::Pending->value,
        ];

        $sale->updateQuietly($updateData);
    }
}
