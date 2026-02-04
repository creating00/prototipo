<?php

namespace App\Services\Sale\Traits;

use App\Models\Sale;
use App\Enums\CurrencyType;

trait HandlesSalePayments
{
    protected function processPayments(Sale $sale, array $data, array $totals, callable $addPaymentCallback): void
    {
        $isDual = isset($data['enable_dual_payment']) && (int)$data['enable_dual_payment'] === 1;
        $totalToPay = array_sum(array_map('floatval', $totals));

        // Determinar moneda del pago
        // Si es dual, forzamos ARS. Si es simple, verificamos si la venta está en USD.
        $paymentCurrency = CurrencyType::ARS;

        if (!$isDual) {
            $paymentCurrency = isset($totals[CurrencyType::USD->value])
                ? CurrencyType::USD
                : CurrencyType::ARS;
        }

        // Pago 1
        if (!empty($data['amount_received'])) {
            $amount1 = $isDual ? (float)$data['amount_received'] : min((float)$data['amount_received'], $totalToPay);

            $addPaymentCallback($sale, $this->buildPaymentData([
                'payment_type'        => $data['payment_type'],
                'amount'              => $amount1,
                'currency'            => $paymentCurrency, // Usar la moneda detectada
                'bank_id'             => $data['payment_method_id'] ?? null,
                'bank_account_id'     => $data['payment_method_id'] ?? null,
                'payment_method_type' => $data['payment_method_type'] ?? null,
                'payment_notes'       => $data['payment_notes'] ?? null,
            ]));
        }

        // Pago 2 (Siempre ARS según tu requerimiento actual)
        if ($isDual && !empty($data['amount_received_2'])) {
            $addPaymentCallback($sale, $this->buildPaymentData([
                'payment_type'        => $data['payment_type_2'],
                'amount'              => (float)$data['amount_received_2'],
                'currency'            => CurrencyType::ARS, // Forzado a ARS
                'bank_id'             => $data['payment_method_id_2'] ?? null,
                'bank_account_id'     => $data['payment_method_id_2'] ?? null,
                'payment_method_type' => $data['payment_method_type_2'] ?? null,
                'payment_notes'       => $data['payment_notes'] ?? null,
            ]));
        }
    }

    protected function buildPaymentData(array $data): array
    {
        return array_filter([
            'payment_type'        => $data['payment_type'],
            'amount'              => (float) $data['amount'],
            'currency'            => $data['currency'] ?? null,
            'bank_id'             => $data['bank_id'] ?? null,
            'bank_account_id'     => $data['bank_account_id'] ?? null,
            'payment_method_type' => $data['payment_method_type'] ?? null,
            'notes'               => $data['payment_notes'] ?? null,
        ], fn($v) => $v !== null);
    }
}
