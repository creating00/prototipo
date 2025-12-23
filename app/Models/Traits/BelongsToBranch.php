<?php

namespace App\Models\Traits;

use App\Models\Scopes\BranchScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    /**
     * El nombre debe ser exactamente boot seguido del nombre del Trait
     */
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            // Depuración interna: si el branch_id es null, intentamos asignar el del usuario
            if (is_null($model->branch_id)) {
                $model->branch_id = Auth::user()?->branch_id;
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
