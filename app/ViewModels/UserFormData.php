<?php

namespace App\ViewModels;

use App\Models\User;
use Illuminate\Support\Collection;

class UserFormData
{
    public function __construct(
        public readonly ?User $user,
        public readonly Collection $provinces,
        public readonly Collection $branches,
        public readonly Collection $roles,
        public readonly array $statusOptions,
        public readonly ?int $branchUserId = null,
    ) {}

    /**
     * Determina si es una edición.
     */
    public function isEdit(): bool
    {
        return $this->user !== null;
    }
}
