<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy extends BasePolicy
{
    protected string $resource = 'reports';

    public function viewSales(User $user): bool
    {
        return $this->can($user, 'view_sales');
    }

    public function viewExpenses(User $user): bool
    {
        return $this->can($user, 'view_expenses');
    }

    public function viewInventory(User $user): bool
    {
        return $this->can($user, 'view_inventory');
    }

    public function export(User $user): bool
    {
        return $this->can($user, 'export');
    }
}
