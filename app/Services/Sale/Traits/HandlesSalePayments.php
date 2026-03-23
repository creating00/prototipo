<?php

namespace App\Services\Sale\Traits;

use App\Models\Sale;
use App\Enums\CurrencyType;

trait HandlesSalePayments
{
    protected function processPayments(Sale $sale, array $data, array $totals, callable $addPaymentCallback): void
    {
        $isDual = isset($data['enable_dual_payment']) && (int)$data['enable_dual_payment'] === 1;

        // Detectar la moneda de la venta desde el JSON de totals
        $paymentCurrency = isset($totals[CurrencyType::USD->value])
            ? CurrencyType::USD
            : CurrencyType::ARS;

        // --- PAGO 1 (Campos estándar de tu Blade) ---
        if (!empty($data['amount_received']) && (float)$data['amount_received'] > 0) {
            $addPaymentCallback($sale, $this->buildPaymentData([
                'payment_type'        => $data['payment_type'],
                'amount'              => (float)$data['amount_received'],
                'currency'            => $paymentCurrency,
                'bank_id'             => $data['payment_method_id'] ?? null,
                'bank_account_id'     => $data['payment_method_id'] ?? null,
                'payment_method_type' => $data['payment_method_type'] ?? null,
                'payment_notes'       => $data['payment_notes'] ?? null,
            ]));
        }

        // --- PAGO 2 (Campos estándar de tu Blade) ---
        if ($isDual && !empty($data['amount_received_2']) && (float)$data['amount_received_2'] > 0) {
            $addPaymentCallback($sale, $this->buildPaymentData([
                'payment_type'        => $data['payment_type_2'],
                'amount'              => (float)$data['amount_received_2'],
                'currency'            => $paymentCurrency, // Mantenemos la moneda de la venta
                'bank_id'             => $data['payment_method_id_2'] ?? null,
                'bank_account_id'     => $data['payment_method_id_2'] ?? null,
                'payment_method_type' => $data['hidden_payment_method_type_2'] ?? null,
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
