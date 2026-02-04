<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Services\Sale\Traits\HandlesSalePayments;
use App\Services\Sale\Traits\CalculatesSaleTotals;
use Illuminate\Support\Facades\DB;

class SaleUpdater
{
    use HandlesSalePayments, CalculatesSaleTotals;

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
            // 1. Revertir estado previo
            $this->itemProcessor->releaseStock($sale);
            $sale->items()->delete();
            $sale->payments()->delete();

            // 2. Cálculos mediante Trait
            $totals = json_decode($data['totals'] ?? '{}', true);
            $calculated = $this->calculateNormalizedData($data, $totals);

            // 3. Actualizar cabecera
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
                'exchange_rate'     => $calculated['exchange_rate'],
                'amount_received'   => $calculated['amount_received'],
                'change_returned'   => $calculated['change_returned'],
                'remaining_balance' => $calculated['remaining_balance'],
            ]);

            // 4. Sincronizar items
            $this->itemProcessor->sync(
                $sale,
                $prepared['items'],
                $prepared['skip_stock_movement'] ?? false
            );

            // 5. Pagos y Recálculo
            $this->processPayments($sale, $data, $totals, $addPaymentCallback);
            $this->paymentManager->recalculateSalePayments($sale->fresh());

            return $sale->fresh(['items', 'branch', 'customer', 'payments']);
        });
    }
}
