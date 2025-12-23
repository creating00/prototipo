<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->can('sales.view')
            && $user->branch_id === $sale->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->can('sales.update')
            && $user->branch_id === $sale->branch_id
            && $sale->status === 'draft';
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $user->can('sales.cancel')
            && $user->branch_id === $sale->branch_id
            && ! in_array($sale->status, ['cancelled', 'refunded']);
    }

    public function print(User $user, Sale $sale): bool
    {
        return $user->can('sales.print')
            && $user->branch_id === $sale->branch_id;
    }

    public function refund(User $user, Sale $sale): bool
    {
        return $user->can('sales.refund')
            && $user->branch_id === $sale->branch_id
            && $sale->status === 'completed';
    }
}
