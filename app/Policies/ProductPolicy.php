<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy extends BasePolicy
{
    protected string $resource = 'products';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, Product $product): bool
    {
        // Simplificado: Solo valida permiso del usuario
        return $this->can($user, 'update');
    }

    public function delete(User $user, Product $product): bool
    {
        // Simplificado: Solo valida permiso del usuario
        return $this->can($user, 'delete');
    }
}
