<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\RepairType;
use Illuminate\Database\Eloquent\Builder;

class RepairAmount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'user_id',
        'repair_type',
        'amount',
        'active_only_one', // Cambiado
        'ends_at',
    ];

    protected $casts = [
        'repair_type'     => RepairType::class,
        'active_only_one' => 'integer', // Cambiado de boolean a integer para manejar el 1 o null
        'ends_at'         => 'datetime',
        'amount'          => 'float',
    ];

    /* =========================================================
     |  RELACIONES
     |=========================================================*/

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* =========================================================
     |  SCOPES
     |=========================================================*/

    public function scopeActive(Builder $query): Builder
    {
        // Ahora el activo es aquel que tiene el flag en 1
        return $query->where('active_only_one', 1);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForRepairType(Builder $query, RepairType|int $type): Builder
    {
        return $query->where(
            'repair_type',
            $type instanceof RepairType ? $type->value : $type
        );
    }

    /* =========================================================
     |  HELPERS
     |=========================================================*/

    /**
     * Determina si el registro es el vigente actual.
     */
    public function isActive(): bool
    {
        return $this->active_only_one === 1;
    }

    public function repairTypeLabel(): string
    {
        return $this->repair_type->label();
    }
}
