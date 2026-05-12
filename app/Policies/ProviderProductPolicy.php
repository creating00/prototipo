<?php

namespace App\Policies;

use App\Models\User;

class ProviderProductPolicy extends BasePolicy
{
    protected string $resource = 'provider_products';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, $model): bool
    {
        return $this->can($user, 'update');
    }

    public function delete(User $user, $model): bool
    {
        return $this->can($user, 'delete');
    }
}
