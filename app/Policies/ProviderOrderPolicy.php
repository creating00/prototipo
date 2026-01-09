<?php

namespace App\Policies;

use App\Models\User;

class ProviderOrderPolicy extends BasePolicy
{
    protected string $resource = 'provider_orders';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, $order): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, $order): bool
    {
        return $this->can($user, 'update');
    }

    public function cancel(User $user, $order): bool
    {
        return $this->can($user, 'cancel');
    }

    public function approve(User $user, $order): bool
    {
        return $this->can($user, 'approve');
    }

    public function print(User $user, $order): bool
    {
        return $this->can($user, 'print');
    }
}
