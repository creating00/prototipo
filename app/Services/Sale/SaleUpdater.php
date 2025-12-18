<?php

namespace App\Services\Sale;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleUpdater
{
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
     * Actualiza un registro de venta existente, resincroniza ítems y recalcula pagos.
     *
     * @param Sale $sale Instancia de la venta a actualizar.
     * @param array $data Datos de actualización.
     * @param callable $addPaymentCallback Función para añadir el pago.
     * @return Sale
     */
    public function update(Sale $sale, array $data, callable $addPaymentCallback): Sale
    {
        $prepared = $this->dataProcessor->prepare($data);

        return DB::transaction(function () use ($sale, $prepared, $addPaymentCallback) {
            // Liberar stock de items actuales antes de la eliminación y resincronización
            $this->itemProcessor->releaseStock($sale);
            $sale->items()->delete();

            // Actualizar registro principal de venta
            $this->updateSaleRecord($sale, $prepared);

            // Sincronizar items y obtener nuevo total
            $total = $this->itemProcessor->sync($sale, $prepared['items']);

            // Recalcular campos de pago basados en el nuevo total
            $paymentUpdateData = $this->calculatePaymentFields($total, $prepared, $sale);
            $paymentUpdateData['total_amount'] = $total;

            $sale->update($paymentUpdateData);

            // Si hay información de pago explícita en la actualización (se asume reemplazo de pagos)
            if (isset($prepared['payment']) && $prepared['payment']) {
                $sale->payments()->delete();
                $addPaymentCallback($sale, $prepared['payment']);
            }

            // Recalcular balance y estado basado en el nuevo total y pagos existentes/nuevos
            $this->recalculatePaymentFieldsFromExistingPayments($sale);

            return $sale->fresh(['items', 'branch', 'customer', 'payments']);
        });
    }

    /**
     * Actualiza los campos principales del registro de venta.
     *
     * @param Sale $sale
     * @param array $saleData
     * @return void
     */
    protected function updateSaleRecord(Sale $sale, array $saleData): void
    {
        $updateData = [
            'branch_id'     => $saleData['branch_id'],
            'user_id'       => $saleData['user_id'] ?? $sale->user_id,
            'sale_type'     => $saleData['sale_type'],
            'status'        => $saleData['status'],
            'customer_id'   => $saleData['customer_id'],
            'customer_type' => $saleData['customer_type'],
            'sale_date'     => $saleData['sale_date'] ?? $sale->sale_date,
            'notes'         => $saleData['notes'] ?? $sale->notes,
        ];

        // Solo actualizar campos de pago si se proporcionan explícitamente en los datos
        if (isset($saleData['amount_received'])) {
            $updateData['amount_received'] = $saleData['amount_received'];
        }

        if (isset($saleData['change_returned'])) {
            $updateData['change_returned'] = $saleData['change_returned'];
        }

        if (isset($saleData['remaining_balance'])) {
            $updateData['remaining_balance'] = $saleData['remaining_balance'];
        }

        $sale->update($updateData);
    }

    /**
     * Calcula los campos de balance y estado basados en el nuevo total y la información de pago.
     * Nota: Este cálculo solo se usa si se proporciona un nuevo pago en la actualización.
     *
     * @param float $total Monto total real de la venta.
     * @param array $prepared Datos procesados.
     * @param Sale $originalSale Instancia original de la venta.
     * @return array
     */
    protected function calculatePaymentFields(float $total, array $prepared, Sale $originalSale): array
    {
        $paymentFields = [];

        // Si hay información de pago en la actualización
        if (isset($prepared['payment']) && isset($prepared['payment']['amount'])) {
            $paymentAmount = $prepared['payment']['amount'];
            $amountReceived = $prepared['amount_received'] ?? $paymentAmount;

            $paymentFields['amount_received'] = $amountReceived;

            if ($amountReceived >= $total) {
                // Pago completo o con cambio
                $paymentFields['change_returned'] = $amountReceived - $total;
                $paymentFields['remaining_balance'] = 0;

                if ($paymentAmount >= $total) {
                    $paymentFields['status'] = 1; // Pagado
                }
            } else {
                // Pago parcial
                $paymentFields['change_returned'] = 0;
                $paymentFields['remaining_balance'] = $total - $paymentAmount;
                $paymentFields['status'] = 2; // Pendiente
            }
        } else {
            // Si no hay pago nuevo, se recalculan los balances basados en el total y lo ya pagado
            $paymentFields['remaining_balance'] = max(0, $total - $originalSale->amount_received);

            if ($originalSale->amount_received > $total) {
                $paymentFields['change_returned'] = $originalSale->amount_received - $total;
            } else {
                $paymentFields['change_returned'] = 0;
            }

            if ($paymentFields['remaining_balance'] <= 0) {
                $paymentFields['status'] = 1; // Pagado
            } else {
                // Mantener el estado actual (siempre que no haya sido Pagado)
                $paymentFields['status'] = $originalSale->status;
            }
        }

        return $paymentFields;
    }

    /**
     * Recalcula los campos de balance y estado de la venta basándose en la suma de los pagos asociados.
     *
     * @param Sale $sale
     * @return void
     */
    protected function recalculatePaymentFieldsFromExistingPayments(Sale $sale): void
    {
        $totalPaid = $sale->payments()->sum('amount');
        $totalAmount = $sale->total_amount;

        $updateData = [
            'amount_received' => $totalPaid,
        ];

        if ($totalPaid >= $totalAmount) {
            $updateData['change_returned'] = $totalPaid - $totalAmount;
            $updateData['remaining_balance'] = 0;
            $updateData['status'] = 1; // Pagado
        } else {
            $updateData['change_returned'] = 0;
            $updateData['remaining_balance'] = $totalAmount - $totalPaid;

            // Si la venta no estaba ya pagada (status 1), se pone como Pendiente (status 2)
            if ($sale->status != 1) {
                $updateData['status'] = 2; // Pendiente
            }
        }

        $sale->update($updateData);
    }
}
