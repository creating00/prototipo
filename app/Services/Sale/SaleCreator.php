<?php

namespace App\Services\Sale;

use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Models\Sale;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class SaleCreator
{
    use AuthTrait;

    protected SaleDataProcessor $dataProcessor;
    protected SaleItemProcessor $itemProcessor;

    public function __construct(
        SaleDataProcessor $dataProcessor,
        SaleItemProcessor $itemProcessor
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->itemProcessor = $itemProcessor;
    }

    /**
     * Crea un nuevo registro de venta, sincroniza ítems, y procesa el pago inicial.
     *
     * @param array $data Datos de la venta.
     * @param callable $addPaymentCallback Función para añadir el pago.
     * @return Sale
     */
    public function create(array $data, callable $addPaymentCallback): Sale
    {
        $prepared = $this->dataProcessor->prepare($data);

        return DB::transaction(function () use ($prepared, $addPaymentCallback) {
            $sale = $this->createSaleRecord($prepared);
            $this->itemProcessor->sync($sale, $prepared['items']);
            $total = (float) $prepared['total'];

            Log::debug('Total de venta resuelto', [
                'sale_id' => $sale->id,
                'sale_type' => $sale->sale_type->name,
                'total' => $total,
            ]);

            // Calcular los campos de pago basados en el total real
            $updateData = $this->calculatePaymentFields($total, $prepared);
            $updateData['total_amount'] = $total;

            $sale->update($updateData);

            if (isset($prepared['payment']) && $prepared['payment']) {
                $addPaymentCallback($sale, $prepared['payment']);
            }

            return $sale->fresh(['items', 'branch', 'customer']);
        });
    }

    /**
     * Crea el registro inicial de la venta y genera el número interno.
     *
     * @param array $saleData Datos procesados de la venta.
     * @return Sale
     */
    protected function createSaleRecord(array $saleData): Sale
    {
        DB::table('sales_internal_numbers')->updateOrInsert(
            ['branch_id' => $saleData['branch_id']],
            ['value' => DB::raw('COALESCE(value, 0)')]
        );

        DB::table('sales_internal_numbers')
            ->where('branch_id', $saleData['branch_id'])
            ->lockForUpdate()
            ->increment('value');

        $internalNumber = DB::table('sales_internal_numbers')
            ->where('branch_id', $saleData['branch_id'])
            ->value('value');

        return Sale::create([
            'branch_id'       => $saleData['branch_id'],
            'user_id'         => $saleData['user_id'] ?? $this->userId(),
            'sale_type'       => $saleData['sale_type'],
            'status'          => $saleData['status'],
            'internal_number' => $internalNumber,
            'total_amount'    => 0,
            'customer_id'     => $saleData['customer_id'],
            'customer_type'   => $saleData['customer_type'],
            'sale_date'       => $saleData['sale_date'] ?? now(),
            'notes'           => $saleData['notes'] ?? null,
            'amount_received' => 0,
            'change_returned' => 0,
            'remaining_balance' => 0,
        ]);
    }

    /**
     * Calcula los campos de balance y estado basados en el total y el pago inicial.
     *
     * @param float $total Monto total real de la venta.
     * @param array $prepared Datos procesados de la venta.
     * @return array
     */
    protected function calculatePaymentFields(float $total, array $prepared): array
    {
        $amountReceived = (float) ($prepared['amount_received'] ?? 0);

        $changeReturned = max(0, $amountReceived - $total);
        $remainingBalance = max(0, $total - $amountReceived);

        return [
            'amount_received'   => $amountReceived,
            'change_returned'   => $changeReturned,
            'remaining_balance' => $remainingBalance,
            'status' => $remainingBalance == 0
                ? SaleStatus::Paid->value
                : SaleStatus::Pending->value,
        ];
    }
}
