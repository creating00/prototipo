<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends BasePolicy
{
    protected string $resource = 'orders';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, Order $order): bool
    {
        return $this->can($user, 'view');
    }

    public function viewOwn(User $user): bool
    {
        return $this->can($user, 'view_own');
    }

    public function createClient(User $user): bool
    {
        return $this->can($user, 'create_client');
    }

    public function createBranch(User $user): bool
    {
        return $this->can($user, 'create_branch');
    }

    public function update(User $user, Order $order): bool
    {
        return $this->can($user, 'update');
    }

    public function approve(User $user, Order $order): bool
    {
        return $this->can($user, 'approve');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $this->can($user, 'cancel');
    }
}
