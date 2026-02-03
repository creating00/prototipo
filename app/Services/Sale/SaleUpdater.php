<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Services\Sale\Traits\HandlesSalePayments;
use Illuminate\Support\Facades\DB;

class SaleUpdater
{
    use HandlesSalePayments;
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

            // 4. Lógica de Moneda (Igual a Creator)
            $totals = json_decode($data['totals'] ?? '{}', true);
            $isDollarSale = isset($totals[2]);
            $rate = (float) ($data['exchange_rate_blue'] ?? 1);

            // Normalización de balance a moneda base
            $rawBalance = (float) ($data['remaining_balance'] ?? 0);
            $normalizedBalance = $isDollarSale ? ($rawBalance * $rate) : $rawBalance;

            // 5. Actualizar cabecera
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
                'exchange_rate'     => $rate,
                'amount_received'   => (float)($data['amount_received'] ?? 0),
                'change_returned'   => (float)($data['change_returned'] ?? 0),
                'remaining_balance' => $normalizedBalance,
            ]);

            // 6. Registro de Pagos (Usando la lógica centralizada)
            $this->processPayments($sale, $data, $totals, $addPaymentCallback);

            // 7. Recalcular
            $this->paymentManager->recalculateSalePayments($sale->fresh());

            return $sale->fresh(['items', 'branch', 'customer', 'payments']);
        });
    }
}
