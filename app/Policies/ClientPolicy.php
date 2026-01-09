<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy extends BasePolicy
{
    protected string $resource = 'clients';

    public function viewAny(User $user): bool
    {
        return $this->can($user, 'view');
    }

    public function view(User $user, Client $client): bool
    {
        return $this->can($user, 'view');
    }

    public function create(User $user): bool
    {
        return $this->can($user, 'create');
    }

    public function update(User $user, Client $client): bool
    {
        // Simplificado: solo valida permiso del usuario
        return $this->can($user, 'update');
    }

    public function delete(User $user, Client $client): bool
    {
        // Simplificado: solo valida permiso del usuario
        return $this->can($user, 'delete');
    }
}
