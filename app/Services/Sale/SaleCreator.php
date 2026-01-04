<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Services\PriceAuditService;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;

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
        $prepared = $this->dataProcessor->prepare($data);

        return DB::transaction(function () use ($prepared, $addPaymentCallback) {
            $internalNumber = $this->generateInternalNumber($prepared['branch_id']);

            // Creación en un solo paso (evita doble update)
            $sale = Sale::create(array_merge($prepared, [
                'internal_number' => $internalNumber,
                'subtotal_amount' => $prepared['subtotal'],
                'total_amount'    => $prepared['total'],
                'user_id'         => $prepared['user_id'] ?? $this->userId(),
                'notes'           => $prepared['notes'] ?? null,
            ]));

            // Sincronizar items y stock
            $this->itemProcessor->sync(
                $sale,
                $prepared['items'],
                $prepared['skip_stock_movement'] ?? false
            );

            // Procesar pago inicial
            if (!empty($prepared['payment'])) {
                $addPaymentCallback($sale, $prepared['payment']);
            }

            return $sale->fresh(['items', 'branch', 'customer', 'payments']);
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
