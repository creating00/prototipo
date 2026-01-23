<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Services\PriceAuditService;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleCreator
{
    use AuthTrait;

    protected SaleDataProcessor $dataProcessor;
    protected SaleItemProcessor $itemProcessor;
    protected PriceAuditService $auditService;

    public function __construct(
        SaleDataProcessor $dataProcessor,
        SaleItemProcessor $itemProcessor,
        PriceAuditService $auditService,
    ) {
        $this->dataProcessor = $dataProcessor;
        $this->itemProcessor = $itemProcessor;
        $this->auditService = $auditService;
    }

    public function create(array $data, callable $addPaymentCallback): Sale
    {
        // Preparamos datos básicos (clientes, items, etc)
        $prepared = $this->dataProcessor->prepare($data);

        // dd([
        //     'prepared_data' => $prepared,
        //     'original_data' => $data
        // ]);

        return DB::transaction(function () use ($prepared, $addPaymentCallback, $data) {
            $internalNumber = $this->generateInternalNumber($prepared['branch_id']);

            // Forzamos los valores numéricos desde $data para evitar que prepare() los omita
            $finalData = array_merge($prepared, [
                'internal_number'   => $internalNumber,
                'user_id'           => $prepared['user_id'] ?? $this->userId(),
                'amount_received'   => (float) ($data['amount_received'] ?? 0),
                'change_returned'   => (float) ($data['change_returned'] ?? 0),
                'remaining_balance' => (float) ($data['remaining_balance'] ?? 0),
            ]);

            // 1. Creación inicial
            $sale = Sale::create($finalData);

            // dd([
            //     'model_attributes_after_create' => $sale->getAttributes(),
            //     'was_recently_created' => $sale->wasRecentlyCreated,
            // ]);

            $totals = json_decode($data['totals'], true);
            
            // 2. Sincronizar items
            $this->itemProcessor->sync(
                $sale,
                $prepared['items'],
                $prepared['skip_stock_movement'] ?? false
            );
            
            // 3. Guardado final de totales (usamos update para no disparar eventos innecesarios)
            $sale->updateQuietly([
                'totals' => $totals
            ]);

            // dd([
            //     'stage' => 'after_payment_callback',
            //     'change_returned' => $sale->fresh()->change_returned
            // ]);

            // Registro del pago
            if (!empty($data['payment_type'])) {
                $addPaymentCallback($sale, [
                    'payment_type'    => $data['payment_type'],
                    'amount'          => min((float)$data['amount_received'], array_sum(array_map('floatval', $totals))),
                    'notes'           => $data['payment_notes'] ?? null,
                    'amount_received' => (float)$data['amount_received'],
                    'change_returned' => (float)$data['change_returned'],
                ]);
            }

            return $sale->fresh(['items', 'payments']);
        });
    }

    protected function generateInternalNumber(int $branchId): int
    {
        // Asegurar existencia del contador
        DB::table('sales_internal_numbers')->updateOrInsert(
            ['branch_id' => $branchId],
            ['value' => DB::raw('COALESCE(value, 0)')]
        );

        // Incrementar con bloqueo
        DB::table('sales_internal_numbers')
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->increment('value');

        return DB::table('sales_internal_numbers')
            ->where('branch_id', $branchId)
            ->value('value');
    }
}
