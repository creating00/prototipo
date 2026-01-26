<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Enums\SaleStatus;
use Illuminate\Support\Facades\Log;

class SalePaymentRecalculator
{
    /**
     * Recalcula los campos de pago y estado basándose únicamente en los payments.
     */
    public function recalculate(Sale $sale): void
    {
        $sale->refresh();

        // Calculamos el total de items manualmente para asegurar que no hay delay de DB
        $itemsTotal = (float) $sale->items()->get()->sum('subtotal');
        $discount = (float) $sale->discount_amount;
        $totalAmount = round($itemsTotal - $discount, 2);

        $totalPaid = (float) $sale->payments()->sum('amount');

        $headerReceived = (float) $sale->amount_received;
        $finalReceived = max($headerReceived, $totalPaid);

        $isPaid = ($totalPaid >= ($totalAmount - 0.01));

        if ($isPaid && $totalAmount > 0) {
            $updateData = [
                'amount_received'   => $finalReceived,
                'change_returned'   => round(max(0, $finalReceived - $totalAmount), 2),
                'remaining_balance' => 0,
                'status'            => SaleStatus::Paid->value,
            ];
        } else {
            $updateData = [
                'amount_received'   => $finalReceived,
                'change_returned'   => 0,
                'remaining_balance' => round(max(0, $totalAmount - $totalPaid), 2),
                'status'            => SaleStatus::Pending->value,
            ];
        }

        $sale->update($updateData);
    }
}
