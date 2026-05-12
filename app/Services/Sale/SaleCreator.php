<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Services\PriceAuditService;
use App\Services\Sale\Traits\HandlesSalePayments;
use App\Services\Sale\Traits\CalculatesSaleTotals;
use App\Services\Sale\Traits\NormalizesSaleInput;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;

class SaleCreator
{
    use AuthTrait, HandlesSalePayments, CalculatesSaleTotals, NormalizesSaleInput;

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

        return DB::transaction(function () use ($prepared, $addPaymentCallback, $data) {
            $internalNumber = $this->generateInternalNumber($prepared['branch_id']);

            // 1. Cálculos mediante Trait (Unificado con Updater)
            $totals = json_decode($data['totals'] ?? '{}', true);

            // 1.5. Normalización: Ajusta el monto Y modifica el array $totals si es menor
            // Pasamos $totals para que se actualice internamente si es necesario
            $data['amount_received'] = $this->resolveAndAdjustTotals($data, $totals);

            $calculated = $this->calculateNormalizedData($data, $totals);

            // 2. Preparación de datos finales
            $finalData = array_merge($prepared, [
                'internal_number'   => $internalNumber,
                'user_id'           => $prepared['user_id'] ?? $this->userId(),
                'exchange_rate'     => $calculated['exchange_rate'],
                'amount_received'   => $calculated['amount_received'],
                'change_returned'   => $calculated['change_returned'],
                'remaining_balance' => $calculated['remaining_balance'],
                'totals'            => $totals, // Se incluye directamente en la creación
            ]);

            // 3. Creación de la venta
            $sale = Sale::create($finalData);

            // 4. Sincronizar items
            $this->itemProcessor->sync(
                $sale,
                $prepared['items'],
                $prepared['skip_stock_movement'] ?? false
            );

            // 5. Registro de Pagos
            $this->processPayments($sale, $data, $totals, $addPaymentCallback);

            return $sale->fresh(['items', 'payments']);
        });
    }

    protected function generateInternalNumber(int $branchId): int
    {
        DB::table('sales_internal_numbers')->updateOrInsert(
            ['branch_id' => $branchId],
            ['value' => DB::raw('COALESCE(value, 0)')]
        );

        DB::table('sales_internal_numbers')
            ->where('branch_id', $branchId)
            ->lockForUpdate()
            ->increment('value');

        return DB::table('sales_internal_numbers')
            ->where('branch_id', $branchId)
            ->value('value');
    }
}
