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

        return DB::transaction(function () use ($sale, $prepared, $addPaymentCallback) {
            // 1. Resetear stock e items
            $this->itemProcessor->releaseStock($sale);
            $sale->items()->delete();

            // 2. Sincronizar nuevos items (esto actualiza subtotal y total en DB)
            $this->itemProcessor->sync($sale, $prepared['items']);

            // 3. Actualizar datos de cabecera (incluye notas)
            $sale->update([
                'branch_id'       => $prepared['branch_id'],
                'customer_id'     => $prepared['customer_id'],
                'customer_type'   => $prepared['customer_type'],
                'sale_date'       => $prepared['sale_date'],
                'notes'           => $prepared['notes'] ?? null,
                'sale_type'       => $prepared['sale_type'],
                'discount_id'     => $prepared['discount_id'] ?? null,
                'discount_amount' => $prepared['discount_amount'] ?? 0,
                'subtotal_amount' => $prepared['subtotal'],
                'total_amount'    => $prepared['total'],
            ]);

            // 4. Gestionar pagos
            if (!empty($prepared['payment'])) {
                $existingPayment = $sale->payments()->first();

                if ($existingPayment) {
                    $existingPayment->update([
                        'payment_type' => $prepared['payment']['payment_type'],
                        'amount'       => $prepared['payment']['amount'],
                        'notes'        => $prepared['payment']['notes'],
                        'reference'    => $prepared['payment']['reference'],
                    ]);
                } else {
                    $addPaymentCallback($sale, $prepared['payment']);
                }
            }

            // 5. Recalcular saldo y estado final
            // Este paso es el que manda sobre el estado (Paid/Pending)
            $this->paymentManager->recalculateSalePayments($sale);

            return $sale->fresh(['items', 'branch', 'customer', 'payments']);
        });
    }
}
