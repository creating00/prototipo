<?php

namespace App\Policies;

use App\Models\User;

class ProviderPolicy extends BasePolicy
{
    protected string $resource = 'providers';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, $provider): bool
    {
        return $this->can($user, 'update')
            && $this->sameBranch($user, $provider);
    }

    public function delete(User $user, $provider): bool
    {
        return $this->can($user, 'delete')
            && $this->sameBranch($user, $provider);
    }
}
