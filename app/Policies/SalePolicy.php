<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy extends BasePolicy
{
    protected string $resource = 'sales';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $this->can($user, 'view');
    }

    public function createClient(User $user): bool
    {
        return $this->can($user, 'create_client');
    }

    public function createBranch(User $user): bool
    {
        return $this->can($user, 'create_branch');
    }

    public function cancel(User $user, Sale $sale): bool
    {
        return $this->can($user, 'cancel');
    }

    public function print(User $user, Sale $sale): bool
    {
        return $this->can($user, 'print');
    }

    public function refund(User $user, Sale $sale): bool
    {
        return $this->can($user, 'refund');
    }
}
