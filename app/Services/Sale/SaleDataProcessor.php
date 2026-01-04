<?php

namespace App\Services\Sale;

use App\Services\Sale\SaleTotalResolver;
use App\Traits\CalculatesSubtotalFromItems;

class SaleDataProcessor
{
    use CalculatesSubtotalFromItems;

    protected SaleTotalResolver $totalResolver;

    public function __construct(SaleTotalResolver $totalResolver)
    {
        $this->totalResolver = $totalResolver;
    }

    public function prepare(array $validated): array
    {
        $this->processCustomerData($validated);

        $validated['items'] = $this->prepareItems($validated['items'] ?? []);
        $validated['subtotal'] = $this->calculateSubtotalFromItems($validated['items']);

        // Calcular el monto del descuento usando el método existente
        $validated['discount_amount'] = $this->resolveDiscountAmount($validated);

        $validated['total'] = $this->totalResolver->resolve($validated);

        $this->processSalePaymentFields($validated, $validated['total']);

        if (isset($validated['payment_type'])) {
            $validated['payment'] = $this->preparePayment($validated, $validated['total']);
        }

        return $validated;
    }

    protected function processCustomerData(array &$data): void
    {
        if ($data['customer_type'] === \App\Models\Client::class) {
            $data['customer_id'] = $data['client_id'];
            unset($data['client_id']);
        }

        if ($data['customer_type'] === \App\Models\Branch::class) {
            $data['customer_id'] = $data['branch_recipient_id'];
            unset($data['branch_recipient_id']);
        }
    }

    protected function prepareItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal'   => $item['quantity'] * $item['unit_price'],
            ];
        }, $items);
    }

    protected function preparePayment(array $data, float $total): array
    {
        $amountReceived = $data['amount_received'] ?? 0;

        if ($amountReceived <= 0 && isset($data['id'])) {
            return [];
        }

        // El payment_amount (amount) es el mínimo entre amount_received y total
        $paymentAmount = min($amountReceived, $total);

        return [
            'payment_type' => $data['payment_type'],
            'amount' => $paymentAmount, // Este es el monto que realmente se aplica al pago
            'notes' => $data['payment_notes'] ?? null,
            'reference' => $data['payment_reference'] ?? null,
            'amount_received' => $amountReceived,
            'change_returned' => $data['change_returned'] ?? 0,
        ];
    }

    protected function processSalePaymentFields(array &$data, float $total): void
    {
        // Si la llave no existe, es null o es una cadena vacía ('')
        if (!isset($data['amount_received']) || $data['amount_received'] === null || $data['amount_received'] === '') {
            $amountReceived = $total;
        } else {
            // Si el usuario escribió algo (incluyendo un 0 manual), usamos ese valor
            $amountReceived = (float) $data['amount_received'];
        }

        $data['amount_received'] = $amountReceived;

        // Calcular cambio
        if (!isset($data['change_returned'])) {
            $data['change_returned'] = max(0, $amountReceived - $total);
        }

        // Calcular saldo pendiente
        $data['remaining_balance'] = max(0, $total - $amountReceived);
    }

    protected function resolveDiscountAmount(array $data): float
    {
        if (empty($data['discount_id'])) {
            return 0.0;
        }

        $discount = \App\Models\Discount::active()->find($data['discount_id']);

        if (!$discount) {
            return 0.0;
        }

        return $discount->calculateAmount($data['subtotal']);
    }
}
