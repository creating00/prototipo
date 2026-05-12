<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    /**
     * Aplica el filtro por branch_id del usuario autenticado.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->branch_id) {
            $builder->where($model->getTable() . '.branch_id', Auth::user()->branch_id);
        }
    }
}