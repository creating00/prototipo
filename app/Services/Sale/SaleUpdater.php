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

            // 2. Limpiar pagos existentes para re-procesar según el nuevo estado del switch
            // Esto asegura que si pasa de Dual a Single, no quede un pago huérfano
            $sale->payments()->delete();

            // 3. Re-sincronizar items
            $this->itemProcessor->sync($sale, $prepared['items']);

            // 4. Actualizar cabecera (Sincronizamos con los hiddens de la UI)
            $sale->update([
                'branch_id'         => $prepared['branch_id'],
                'customer_id'       => $prepared['customer_id'],
                'customer_type'     => $prepared['customer_type'],
                'sale_date'         => $prepared['sale_date'],
                'notes'             => $prepared['notes'] ?? null,
                'sale_type'         => $prepared['sale_type'],
                'discount_id'       => $prepared['discount_id'] ?? null,
                'discount_amount'   => (float)($prepared['discount_amount'] ?? 0),
                'totals'            => json_decode($data['totals'], true),
                'requires_invoice'  => $prepared['requires_invoice'] ?? false,
                'amount_received'   => (float)$data['amount_received'],
                'change_returned'   => (float)$data['change_returned'],
                'remaining_balance' => (float)$data['remaining_balance'],
            ]);

            // 5. Gestión de Pagos (Nueva Lógica Dual/Single)
            $isDual = isset($data['enable_dual_payment']) && (int)$data['enable_dual_payment'] === 1;

            if ($isDual) {
                // Pago 1
                if (!empty($data['amount_received'])) {
                    $addPaymentCallback($sale, [
                        'payment_type' => $data['payment_type'],
                        'amount'       => (float)$data['amount_received'],
                        'notes'        => $data['payment_notes'] ?? null,
                    ]);
                }
                // Pago 2
                if (!empty($data['amount_received_2']) && (float)$data['amount_received_2'] > 0) {
                    $addPaymentCallback($sale, [
                        'payment_type' => $data['payment_type_2'],
                        'amount'       => (float)$data['amount_received_2'],
                        'notes'        => $data['payment_notes'] ?? null,
                    ]);
                }
            } else {
                // Pago Único
                if (!empty($data['payment_type'])) {
                    $addPaymentCallback($sale, [
                        'payment_type' => $data['payment_type'],
                        'amount'       => (float)$data['amount_received'], // Se toma el recibido total
                        'notes'        => $data['payment_notes'] ?? null,
                    ]);
                }
            }

            // 6. El Recalculator hará el trabajo final de ajustar estatus y saldos
            $this->paymentManager->recalculateSalePayments($sale->fresh());

            return $sale->fresh(['items', 'branch', 'customer', 'payments']);
        });
    }
}
