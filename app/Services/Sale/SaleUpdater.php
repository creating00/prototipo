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

            // 1. Resetear items y stock
            $this->itemProcessor->releaseStock($sale);
            $sale->items()->delete();

            // 2. Re-sincronizar items y obtener totales reales
            $totals = $this->itemProcessor->sync(
                $sale,
                $prepared['items']
            );

            // Calculamos el total numérico para la cabecera sumando los subtotales por moneda
            $totalCalculado = array_sum(array_map('floatval', $totals));

            // 3. Actualizar cabecera de la venta
            $sale->update([
                'branch_id'         => $prepared['branch_id'],
                'customer_id'       => $prepared['customer_id'],
                'customer_type'     => $prepared['customer_type'],
                'sale_date'         => $prepared['sale_date'],
                'notes'             => $prepared['notes'] ?? null,
                'sale_type'         => $prepared['sale_type'],
                'discount_id'       => $prepared['discount_id'] ?? null,
                'discount_amount'   => $prepared['discount_amount'] ?? 0,
                // Usamos el total calculado del sync para asegurar precisión
                'subtotal_amount'   => $totalCalculado,
                'total_amount'      => $totalCalculado,
                'totals'            => $totals,
                'requires_invoice'  => $prepared['requires_invoice'] ?? false,
                'amount_received'   => (float) ($data['amount_received'] ?? 0),
                'change_returned'   => (float) ($data['change_returned'] ?? 0),
                'remaining_balance' => (float) ($data['remaining_balance'] ?? 0),
            ]);

            // 4. Gestión de Pagos
            if (!empty($data['payment_type'])) {
                $existingPayment = $sale->payments()->first();

                $paymentPayload = [
                    'payment_type'    => $data['payment_type'],
                    // El pago no puede ser mayor al nuevo total de la venta
                    'amount'          => min((float)$data['amount_received'], $totalCalculado),
                    'notes'           => $data['payment_notes'] ?? null,
                    'amount_received' => (float)$data['amount_received'],
                    'change_returned' => (float)$data['change_returned'],
                    'reference'       => $data['reference'] ?? null,
                ];

                if ($existingPayment) {
                    $existingPayment->update($paymentPayload);
                } else {
                    $addPaymentCallback($sale, $paymentPayload);
                }
            }

            // 5. Recalcular estado final (Paid/Pending)
            $this->paymentManager->recalculateSalePayments($sale->fresh());

            return $sale->fresh(['items', 'branch', 'customer', 'payments']);
        });
    }
}
