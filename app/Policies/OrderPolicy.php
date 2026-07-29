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
        if (!$this->can($user, 'view')) {
            return false;
        }

        if ($user->hasRole('admin') || !$user->branch_id) {
            return true;
        }

        return (int)$order->branch_id === (int)$user->branch_id
            || ($order->isInterBranch() && (int)$order->customer_id === (int)$user->branch_id);
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
        if (!$this->can($user, 'update')) {
            return false;
        }

        if ($user->hasRole('admin') || !$user->branch_id) {
            return true;
        }

        return (int)$order->branch_id === (int)$user->branch_id
            || ($order->isInterBranch() && (int)$order->customer_id === (int)$user->branch_id);
    }

    public function approve(User $user, Order $order): bool
    {
        if (!$this->can($user, 'approve')) {
            return false;
        }

        if ($user->hasRole('admin') || !$user->branch_id) {
            return true;
        }

        return (int)$order->branch_id === (int)$user->branch_id
            || ($order->isInterBranch() && (int)$order->customer_id === (int)$user->branch_id);
    }

    public function cancel(User $user, Order $order): bool
    {
        if (!$this->can($user, 'cancel')) {
            return false;
        }

        if ($user->hasRole('admin') || !$user->branch_id) {
            return true;
        }

        return (int)$order->branch_id === (int)$user->branch_id
            || ($order->isInterBranch() && (int)$order->customer_id === (int)$user->branch_id);
    }
}
