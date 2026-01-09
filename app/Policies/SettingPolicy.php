<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy extends BasePolicy
{
    protected string $resource = 'settings';

    public function view(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function update(User $user): bool
    {
        return $this->can($user, 'update');
    }
}
