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
        $totalPaid   = $sale->payments()->sum('amount');
        $totalAmount = $sale->total_amount;

        $updateData = [
            'amount_received' => $totalPaid,
        ];

        if ($totalPaid >= $totalAmount) {
            $updateData['change_returned']   = $totalPaid - $totalAmount;
            $updateData['remaining_balance'] = 0;
            $updateData['status']            = SaleStatus::Paid->value;
        } else {
            $updateData['change_returned']   = 0;
            $updateData['remaining_balance'] = $totalAmount - $totalPaid;
            $updateData['status']            = SaleStatus::Pending->value;
        }

        $sale->update($updateData);
    }
}
