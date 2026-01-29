<?php

namespace App\Services\Sale;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleUpdater
{
    protected SaleDataProcessor $dataProcessor;
    protected SaleItemProcessor $itemProcessor;
    protected SalePaymentManager $paymentManager;

    public function __construct(
        SaleDataProcessor $dataProcessor,
        SaleItemProcessor $itemProcessor,
        SalePaymentManager $paymentManager
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->itemProcessor = $itemProcessor;
        $this->paymentManager = $paymentManager;
    }

    public function update(Sale $sale, array $data, callable $addPaymentCallback): Sale
    {
        $prepared = $this->dataProcessor->prepare($data);

        return DB::transaction(function () use ($sale, $prepared, $addPaymentCallback, $data) {
            // 1. Resetear stock e items
            $this->itemProcessor->releaseStock($sale);
            $sale->items()->delete();

            // 2. Limpiar pagos existentes para re-procesar
            $sale->payments()->delete();

            // 3. Re-sincronizar items
            $this->itemProcessor->sync($sale, $prepared['items']);

            // 4. Actualizar cabecera
            $totals = json_decode($data['totals'], true);
            $totalToPay = array_sum(array_map('floatval', $totals));

            $sale->update([
                'branch_id'         => $prepared['branch_id'],
                'customer_id'       => $prepared['customer_id'],
                'customer_type'     => $prepared['customer_type'],
                'sale_date'         => $prepared['sale_date'],
                'notes'             => $prepared['notes'] ?? null,
                'sale_type'         => $prepared['sale_type'],
                'discount_id'       => $prepared['discount_id'] ?? null,
                'discount_amount'   => (float)($prepared['discount_amount'] ?? 0),
                'totals'            => $totals,
                'requires_invoice'  => $prepared['requires_invoice'] ?? false,
                'amount_received'   => (float)$data['amount_received'],
                'change_returned'   => (float)$data['change_returned'],
                'remaining_balance' => (float)$data['remaining_balance'],
            ]);

            // 5. Gestión de Pagos (Sincronizada con Creator)
            $isDual = isset($data['enable_dual_payment']) && (int)$data['enable_dual_payment'] === 1;

            if ($isDual) {
                // Pago 1
                if (!empty($data['amount_received']) && (float)$data['amount_received'] > 0) {
                    $addPaymentCallback($sale, $this->buildPaymentData([
                        'payment_type'        => $data['payment_type'],
                        'amount'              => $data['amount_received'],
                        'bank_id'             => $data['payment_method_id'] ?? null,
                        'bank_account_id'     => $data['payment_method_id'] ?? null,
                        'payment_method_type' => $data['payment_method_type'] ?? null,
                        'payment_notes'       => $data['payment_notes'] ?? null,
                    ]));
                }
                // Pago 2
                if (!empty($data['amount_received_2']) && (float)$data['amount_received_2'] > 0) {
                    $addPaymentCallback($sale, $this->buildPaymentData([
                        'payment_type'        => $data['payment_type_2'],
                        'amount'              => $data['amount_received_2'],
                        'bank_id'             => $data['payment_method_id_2'] ?? null,
                        'bank_account_id'     => $data['payment_method_id_2'] ?? null,
                        'payment_method_type' => $data['payment_method_type_2'] ?? null,
                        'payment_notes'       => $data['payment_notes'] ?? null,
                    ]));
                }
            } else {
                // Pago Único
                if (!empty($data['payment_type'])) {
                    $addPaymentCallback($sale, $this->buildPaymentData([
                        'payment_type'        => $data['payment_type'],
                        'amount'              => min((float)$data['amount_received'], $totalToPay),
                        'bank_id'             => $data['payment_method_id'] ?? null,
                        'bank_account_id'     => $data['payment_method_id'] ?? null,
                        'payment_method_type' => $data['payment_method_type'] ?? null,
                        'payment_notes'       => $data['payment_notes'] ?? null,
                    ]));
                }
            }

            // 6. Recalcular
            $this->paymentManager->recalculateSalePayments($sale->fresh());

            return $sale->fresh(['items', 'branch', 'customer', 'payments']);
        });
    }

    private function buildPaymentData(array $data): array
    {
        return array_filter([
            'payment_type'        => $data['payment_type'],
            'amount'              => (float) $data['amount'],
            'bank_id'             => $data['bank_id'] ?? null,
            'bank_account_id'     => $data['bank_account_id'] ?? null,
            'payment_method_type' => $data['payment_method_type'] ?? null,
            'notes'               => $data['payment_notes'] ?? null,
        ], fn($v) => $v !== null);
    }
}
