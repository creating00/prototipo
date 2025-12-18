<?php

namespace App\Services\Sale;

use App\Models\Payment;
use App\Models\Sale;
use App\Traits\AuthTrait;
use App\Services\PaymentManager;

class SalePaymentManager
{
    use AuthTrait;

    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function addPaymentToSale(Sale $sale, array $paymentData): Payment
    {
        $paymentData['user_id'] = $this->userId();

        if ($sale->customer_type === \App\Models\Branch::class) {
            $branchName = $sale->customer->name ?? 'Sucursal';
            $paymentData['notes'] = 'Transferencia interna a ' . $branchName .
                (isset($paymentData['notes']) ? ' - ' . $paymentData['notes'] : '');
        }

        $paymentAmount = min($paymentData['amount'], $sale->total_amount);

        return Payment::create([
            'payment_type'       => $paymentData['payment_type'],
            'amount'             => $paymentAmount,
            'user_id'            => $paymentData['user_id'] ?? $sale->user_id,
            'paymentable_id'     => $sale->id,
            'paymentable_type'   => get_class($sale),
            'notes'              => $paymentData['notes'] ?? null,
            'reference'          => $paymentData['reference'] ?? null,
            'created_at'         => $sale->sale_date,
        ]);
    }

    public function getPaymentSummary(Sale $sale): array
    {
        return $this->paymentManager->getPaymentSummary($sale);
    }

    public function getPayments(Sale $sale)
    {
        return $sale->payments()->with('user')->get();
    }

    public function processPayment(Sale $sale, array $paymentData): Payment
    {
        return $this->paymentManager->processPayment($sale, $paymentData);
    }
}
