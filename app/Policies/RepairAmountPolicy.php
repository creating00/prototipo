<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RepairAmount;

class RepairAmountPolicy extends BasePolicy
{
    protected string $resource = 'repair_amounts';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, RepairAmount $repairAmount): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }

    public function update(User $user, RepairAmount $repairAmount): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }

    public function delete(User $user, RepairAmount $repairAmount): bool
    {
        return $user->hasRole(\App\Enums\RoleLabel::PROVINCIAL_ADMIN->value);
    }
}
