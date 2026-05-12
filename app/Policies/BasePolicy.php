<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    protected string $resource;

    protected function can(User $user, string $action): bool
    {
        if (!isset($this->resource)) {
            throw new \LogicException('Resource no definido en la Policy');
        }

        return $user->can("{$this->resource}.{$action}");
    }

    protected function sameBranch(User $user, Model $model): bool
    {
        return $user->branch_id === $model->branch_id;
    }
}