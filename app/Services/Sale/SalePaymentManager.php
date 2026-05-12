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
        // Preparar datos específicos de la venta
        $paymentData['user_id'] =
            $paymentData['user_id']
            ?? $sale->user_id
            ?? $this->userId();

        if ($sale->customer_type === \App\Models\Branch::class) {
            $branchName = $sale->customer->name ?? 'Sucursal';
            $paymentData['notes'] = 'Transferencia interna a ' . $branchName .
                (isset($paymentData['notes']) ? ' - ' . $paymentData['notes'] : '');
        }

        // Unificación: Usar el Manager para procesar el pago
        // Esto garantiza que pase por PaymentService -> Factory
        $payment = $this->processPayment($sale, $paymentData);

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
