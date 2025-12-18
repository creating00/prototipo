<?php

namespace App\Services\Sale;

use App\Traits\CalculatesTotalFromItems;
use Illuminate\Support\Facades\Auth;
use App\Services\Sale\SaleTotalResolver;

class SaleDataProcessor
{
    use CalculatesTotalFromItems;
    protected SaleTotalResolver $totalResolver;

    public function __construct(SaleTotalResolver $totalResolver)
    {
        $this->totalResolver = $totalResolver;
    }

    public function prepare(array $validated): array
    {
        $data = $validated;

        $this->processCustomerData($data);
        $data['items'] = $this->prepareItems($data['items'] ?? []);

        // Calcular el total desde los items
        $data['total'] = $this->totalResolver->resolve($data);

        // Procesar los campos de pago de la venta
        $this->processSalePaymentFields($data, $data['total']);

        // Siempre preparar payment si hay payment_type
        if (isset($data['payment_type'])) {
            $data['payment'] = $this->preparePayment($data, $data['total']);
        }

        return $data;
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
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'discount' => $item['discount'] ?? 0,
                'total' => $item['quantity'] * $item['unit_price'] * (1 - ($item['discount'] ?? 0) / 100),
            ];
        }, $items);
    }

    protected function preparePayment(array $data, float $total): array
    {
        $amountReceived = $data['amount_received'] ?? 0;

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
        $amountReceived = $data['amount_received'] ?? 0;

        // Si no se proporciona amount_received, usar 0 como default
        if (!isset($data['amount_received'])) {
            $data['amount_received'] = 0;
        }

        // Calcular change_returned si no se proporciona
        if (!isset($data['change_returned'])) {
            $data['change_returned'] = max(0, $amountReceived - $total);
        }

        // Calcular remaining_balance automáticamente
        $data['remaining_balance'] = max(0, $total - $amountReceived);
    }
}
