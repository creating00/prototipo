<?php

namespace App\Policies;

use App\Models\User;

class RatingPolicy extends BasePolicy
{
    protected string $resource = 'ratings';

    public function view(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function moderate(User $user): bool
    {
        return $this->can($user, 'moderate');
    }

    public function delete(User $user): bool
    {
        return $this->can($user, 'delete');
    }
}