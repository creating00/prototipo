<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    protected string $resource = 'users';

    public function viewAny(User $user): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }

    public function resetPassword(User $user, User $model): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }
}
