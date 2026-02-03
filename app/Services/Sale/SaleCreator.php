<?php

namespace App\Services\Sale;

use App\Models\Sale;
use App\Services\PriceAuditService;
use App\Services\Sale\Traits\HandlesSalePayments;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleCreator
{
    use AuthTrait;
    use HandlesSalePayments;

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

        return DB::transaction(function () use ($prepared, $addPaymentCallback, $data) {
            $internalNumber = $this->generateInternalNumber($prepared['branch_id']);

            // --- Lógica de Moneda ---
            $totals = json_decode($data['totals'] ?? '{}', true);
            $isDollarSale = isset($totals[2]); // ID 2 = Dólares
            $rate = (float) ($data['exchange_rate_blue'] ?? 1);

            // Normalización de balance a moneda base (Pesos)
            $rawBalance = (float) ($data['remaining_balance'] ?? 0);
            $normalizedBalance = $isDollarSale ? ($rawBalance * $rate) : $rawBalance;

            // --- Preparación de Datos Finales ---
            $finalData = array_merge($prepared, [
                'internal_number'   => $internalNumber,
                'user_id'           => $prepared['user_id'] ?? $this->userId(),
                'exchange_rate'     => $rate,
                'amount_received'   => (float) ($data['amount_received'] ?? 0),
                'change_returned'   => (float) ($data['change_returned'] ?? 0),
                'remaining_balance' => $normalizedBalance,
            ]);

            // 1. Creación de la venta
            $sale = Sale::create($finalData);

            // 2. Sincronizar items
            $this->itemProcessor->sync(
                $sale,
                $prepared['items'],
                $prepared['skip_stock_movement'] ?? false
            );

            // 3. Persistencia de totales originales
            $sale->updateQuietly(['totals' => $totals]);

            // 4. Registro de Pagos
            $this->processPayments($sale, $data, $totals, $addPaymentCallback);

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
