<?php

namespace App\Services\Sale;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Discount;
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
        // 1. Mapear IDs
        if (($validated['customer_type'] ?? null) === Client::class) {
            $validated['customer_id'] = $validated['client_id'] ?? null;
        } elseif (($validated['customer_type'] ?? null) === Branch::class) {
            $validated['customer_id'] = $validated['branch_recipient_id'] ?? null;
        }

        // 2. Preparar items crudos
        $validated['items'] = $this->prepareItems($validated['items'] ?? []);

        // 3. No calculamos totales ni balances aquí, los dejamos pasar tal cual vienen 
        // o dejamos que el Creator los maneje tras el sync.

        return $validated;
    }

    protected function prepareItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'currency'   => $item['currency'],
                'subtotal'   => $item['quantity'] * $item['unit_price'],
            ];
        }, $items);
    }

    protected function preparePayment(array $data, float $total): array
    {
        $amountReceived = (float) ($data['amount_received'] ?? 0);

        // El monto aplicado al pago es el mínimo entre lo recibido y el total
        $paymentAmount = min($amountReceived, $total);

        return [
            'payment_type'    => $data['payment_type'],
            'amount'          => $paymentAmount,
            'notes'           => $data['payment_notes'] ?? null,
            'reference'       => $data['payment_reference'] ?? null,
            'amount_received' => $amountReceived,
            'change_returned' => $data['change_returned'] ?? 0,
        ];
    }

    protected function resolveDiscountAmount(array $data): float
    {
        if (empty($data['discount_id'])) {
            return 0.0;
        }

        $discount = Discount::active()->find($data['discount_id']);

        return $discount ? $discount->calculateAmount($data['subtotal']) : 0.0;
    }
}
