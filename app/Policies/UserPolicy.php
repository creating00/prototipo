<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    protected string $resource = 'users';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, User $model): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, User $model): bool
    {
        return $this->can($user, 'update');
    }

    public function delete(User $user, User $model): bool
    {
        return $this->can($user, 'delete');
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $this->can($user, 'assign_roles');
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $this->can($user, 'reset_password');
    }
}
