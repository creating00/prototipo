<?php

namespace App\Services;

use App\Models\RepairAmount;
use App\Enums\RepairType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RepairAmountService
{
    public function create(
        int $branchId,
        RepairType $repairType,
        float $amount,
        ?int $userId = null
    ): RepairAmount {
        return DB::transaction(function () use ($branchId, $repairType, $amount, $userId) {
            // 1. Cerrar el vigente anterior (Setea active_only_one a NULL)
            $this->closeActive($branchId, $repairType);

            // 2. Crear nuevo vigente (active_only_one por defecto es 1)
            return RepairAmount::create([
                'branch_id'       => $branchId,
                'user_id'         => $userId,
                'repair_type'     => $repairType,
                'amount'          => $amount,
                'active_only_one' => 1,
                'ends_at'         => null,
            ]);
        });
    }

    /* =========================================================
     |  READ
     |=========================================================*/

    public function getActive(int $branchId, RepairType $repairType): ?RepairAmount
    {
        return RepairAmount::query()
            ->forBranch($branchId)
            ->forRepairType($repairType)
            ->where('active_only_one', 1) // Filtro por el nuevo campo
            ->first();
    }

    public function getAllForBranch(int $branchId)
    {
        return RepairAmount::query()
            ->forBranch($branchId)
            // Ordenamos: primero el que tiene '1' (el activo), luego los null (históricos)
            ->orderByRaw('active_only_one IS NULL ASC')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getAllForBranchForDatatable(int $branchId): array
    {
        $items = $this->getAllForBranch($branchId);

        return $items->map(function (RepairAmount $item, $index) {
            $isActive = $item->active_only_one === 1;

            return [
                'id'            => $item->id,
                'is_active_raw' => $isActive, // Campo auxiliar para filtrar en el controlador
                'number'        => $index + 1,
                'repair_type'   => "<span class='fw-600 text-dark'>{$item->repair_type->label()}</span>",
                'amount'        => $this->formatAmountHtml($item->amount),
                'status'        => $this->resolveStatusBadge($isActive),
                'ends_at'       => $item->ends_at
                    ? "<span class='small text-muted'><i class='far fa-calendar-alt me-1'></i>{$item->ends_at->format('d/m/Y')}</span>"
                    : '<span class="badge-custom badge-custom-pastel-green">Vigente</span>',
                'created_at'    => "<span class='text-secondary small'>{$item->created_at->format('d/m/Y')}</span>",
            ];
        })->toArray();
    }

    private function resolveStatusBadge(bool $isActive): string
    {
        if ($isActive) {
            return '<span class="badge-custom badge-custom-emerald">
                    <i class="fas fa-check-circle me-1"></i>Activo
                </span>';
        }

        return '<span class="badge-custom badge-custom-silver">
                <i class="fas fa-history me-1"></i>Histórico
            </span>';
    }

    private function formatAmountHtml(float $amount): string
    {
        $formatted = number_format($amount, 2, ',', '.');
        return "<span class='fw-bold text-primary'>$ {$formatted}</span>";
    }

    public function findOrFail(int $id): RepairAmount
    {
        return RepairAmount::findOrFail($id);
    }

    public function update(RepairAmount $repairAmount, array $data): RepairAmount
    {
        return DB::transaction(function () use ($repairAmount, $data) {
            // Si el registro es el activo y el monto cambia...
            if (
                $repairAmount->active_only_one === 1 &&
                array_key_exists('amount', $data) &&
                (float)$data['amount'] !== (float)$repairAmount->amount
            ) {
                // Cerramos este
                $repairAmount->update([
                    'active_only_one' => null, // Libera el índice
                    'ends_at'         => now(),
                ]);

                $this->forgetCache($repairAmount->branch_id, $repairAmount->repair_type);

                // Creamos uno nuevo como activo
                return RepairAmount::create([
                    'branch_id'       => $repairAmount->branch_id,
                    'user_id'         => $data['user_id'] ?? $repairAmount->user_id,
                    'repair_type'     => $repairAmount->repair_type,
                    'amount'          => $data['amount'],
                    'active_only_one' => 1,
                ]);
            }

            // Update normal (si es histórico o no cambia el monto)
            $repairAmount->update(
                collect($data)->except(['active_only_one', 'ends_at'])->toArray()
            );

            return $repairAmount->refresh();
        });
    }

    public function delete(RepairAmount $repairAmount): void
    {
        DB::transaction(function () use ($repairAmount) {
            if ($repairAmount->active_only_one === 1) {
                $repairAmount->update([
                    'active_only_one' => null,
                    'ends_at'         => now(),
                ]);
            }

            $this->forgetCache($repairAmount->branch_id, $repairAmount->repair_type);
            $repairAmount->delete();
        });
    }

    protected function closeActive(int $branchId, RepairType $repairType): void
    {
        RepairAmount::query()
            ->forBranch($branchId)
            ->forRepairType($repairType)
            ->where('active_only_one', 1)
            ->update([
                'active_only_one' => null, // IMPORTANTE: Setea NULL para permitir nuevos registros
                'ends_at'         => now(),
            ]);

        $this->forgetCache($branchId, $repairType);
    }

    public function getActiveAmount(
        int $branchId,
        RepairType $repairType
    ): float {
        return Cache::remember(
            "repair_amount:{$branchId}:{$repairType->value}",
            now()->addMinutes(10),
            function () use ($branchId, $repairType) {
                $repairAmount = $this->getActive($branchId, $repairType);

                if (!$repairAmount) {
                    throw new \RuntimeException('Monto no configurado');
                }

                return (float) $repairAmount->amount;
            }
        );
    }

    protected function forgetCache(
        int $branchId,
        RepairType $repairType
    ): void {
        Cache::forget("repair_amount:{$branchId}:{$repairType->value}");
    }

    public function getActiveAmountOrNull(
        int $branchId,
        RepairType $repairType
    ): ?float {
        return $this->getActive($branchId, $repairType)?->amount;
    }

    public function getActiveAmountOrDefault(
        int $branchId,
        RepairType $repairType,
        float $default = 0.0
    ): float {
        return $this->getActiveAmountOrNull($branchId, $repairType) ?? $default;
    }
}
