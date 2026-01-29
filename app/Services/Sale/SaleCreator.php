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

            $totals = json_decode($data['totals'], true);
            $totalToPay = array_sum(array_map('floatval', $totals));

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

            $isDual = isset($data['enable_dual_payment']) && (int)$data['enable_dual_payment'] === 1;

            // Registro del pago
            if ($isDual) {

                if (!empty($data['amount_received'])) {
                    $addPaymentCallback(
                        $sale,
                        $this->buildPaymentData([
                            'payment_type'    => $data['payment_type'],
                            'amount'          => $data['amount_received'],
                            'bank_id'         => $data['payment_method_id'] ?? null,
                            'bank_account_id' => $data['payment_method_id'] ?? null,
                            'payment_method_type' => $data['payment_method_type'] ?? null,
                            'payment_notes'   => $data['payment_notes'] ?? null,
                        ])
                    );
                }

                if (!empty($data['amount_received_2'])) {
                    $addPaymentCallback(
                        $sale,
                        $this->buildPaymentData([
                            'payment_type'    => $data['payment_type_2'],
                            'amount'          => $data['amount_received_2'],
                            'bank_id'         => $data['payment_method_id_2'] ?? null,
                            'bank_account_id' => $data['payment_method_id_2'] ?? null,
                            'payment_method_type' => $data['payment_method_type_2'] ?? null,
                            'payment_notes'   => $data['payment_notes'] ?? null,
                        ])
                    );
                }
            } else {

                if (!empty($data['payment_type'])) {
                    $addPaymentCallback(
                        $sale,
                        $this->buildPaymentData([
                            'payment_type'    => $data['payment_type'],
                            'amount'          => min((float)$data['amount_received'], $totalToPay),
                            'bank_id'         => $data['payment_method_id'] ?? null,
                            'bank_account_id' => $data['payment_method_id'] ?? null,
                            'payment_method_type' => $data['payment_method_type'] ?? null,
                            'payment_notes'   => $data['payment_notes'] ?? null,
                        ])
                    );
                }
            }

            return $sale->fresh(['items', 'payments']);
        });
    }

    private function buildPaymentData(array $data): array
    {
        return array_filter([
            'payment_type'     => $data['payment_type'],
            'amount'           => (float) $data['amount'],
            'bank_id'          => $data['bank_id'] ?? null,
            'bank_account_id'  => $data['bank_account_id'] ?? null,
            'payment_method_type' => $data['payment_method_type'] ?? null,
            'notes'            => $data['payment_notes'] ?? null,
        ], fn($v) => $v !== null);
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
