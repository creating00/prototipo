<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Enums\SaleStatus;

class SalePaymentRecalculator
{
    /**
     * Recalcula los campos de pago y estado basándose únicamente en los payments.
     */
    public function recalculate(Sale $sale): void
    {
        $totalPaid = (float) $sale->payments()->sum('amount');
        $totalAmount = (float) $sale->items()->sum('subtotal');

        if ($totalPaid >= $totalAmount && $totalAmount > 0) {
            // PAGO TOTAL: Respetamos el efectivo entregado para calcular el vuelto
            $amountReceived = max((float)$sale->amount_received, $totalPaid);

            $updateData = [
                'amount_received'   => $amountReceived,
                'change_returned'   => round($amountReceived - $totalAmount, 2),
                'remaining_balance' => 0,
                'status'            => SaleStatus::Paid->value,
            ];
        } else {
            // PAGO PARCIAL: El recibido es simplemente lo que pagó (no hay vuelto)
            $updateData = [
                'amount_received'   => $totalPaid,
                'change_returned'   => 0,
                'remaining_balance' => round($totalAmount - $totalPaid, 2),
                'status'            => SaleStatus::Pending->value,
            ];
        }

        $sale->update($updateData);
    }
}
