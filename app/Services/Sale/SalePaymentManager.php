<?php

namespace App\Services\Sale;

use App\Enums\CurrencyType;
use App\Models\Payment;
use App\Models\Sale;
use App\Traits\AuthTrait;
use App\Services\PaymentManager;

class SalePaymentManager
{
    use AuthTrait;

    protected PaymentManager $paymentManager;
    protected SalePaymentRecalculator $recalculator;

    public function __construct(
        PaymentManager $paymentManager,
        SalePaymentRecalculator $recalculator
    ) {
        $this->paymentManager = $paymentManager;
        $this->recalculator   = $recalculator;
    }

    public function addPaymentToSale(Sale $sale, array $paymentData): Payment
    {
        $paymentData['user_id'] = $this->userId();

        if ($sale->customer_type === \App\Models\Branch::class) {
            $branchName = $sale->customer->name ?? 'Sucursal';
            $paymentData['notes'] = 'Transferencia interna a ' . $branchName .
                (isset($paymentData['notes']) ? ' - ' . $paymentData['notes'] : '');
        }

        $payment = Payment::create([
            'branch_id' => $sale->branch_id,
            'payment_type'     => $paymentData['payment_type'],
            'amount'           => $paymentData['amount'],
            'currency' => CurrencyType::ARS,
            'user_id'          => $paymentData['user_id'] ?? $sale->user_id,
            'paymentable_id'   => $sale->id,
            'paymentable_type' => get_class($sale),
            'notes'            => $paymentData['notes'] ?? null,
            'reference'        => $paymentData['reference'] ?? null,
        ]);

        $this->recalculateSalePayments($sale);

        return $payment;
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

    public function recalculateSalePayments(Sale $sale): void
    {
        $this->recalculator->recalculate($sale);
    }
}
