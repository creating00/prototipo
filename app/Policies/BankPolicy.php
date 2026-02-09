<?php

namespace App\Policies;

use App\Models\User;

class BanksPolicy extends BasePolicy
{
    protected string $resource = 'banks';

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

    public function update(User $user): bool
    {
        return $this->can($user, 'update');
    }

    public function delete(User $user): bool
    {
        return $this->can($user, 'delete');
    }
}
