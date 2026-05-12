<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy extends BasePolicy
{
    protected string $resource = 'expenses';

    public function view(User $user, Expense $expense): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, Expense $expense): bool
    {
        // Simplificado: solo valida permiso del usuario
        return $this->can($user, 'update');
    }

    public function approve(User $user, Expense $expense): bool
    {
        // Simplificado: solo valida permiso del usuario
        return $this->can($user, 'approve');
    }
}
