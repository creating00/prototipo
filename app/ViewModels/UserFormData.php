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

    public function isEdit(): bool
    {
        return $this->user !== null;
    }

    // --- Métodos de Opciones ---

    public function getRoleOptions(): array
    {
        return $this->roles->pluck('name')->mapWithKeys(function ($name) {
            return [$name => \App\Enums\RoleLabel::labelFrom($name)];
        })->toArray();
    }

    public function getBranchOptions(): array
    {
        return $this->branches->pluck('name', 'id')->toArray();
    }

    // --- Métodos de Valores Seleccionados (Soportan Old Data) ---

    public function getName(): string
    {
        return old('name', $this->user?->name ?? '');
    }

    public function getEmail(): string
    {
        return old('email', $this->user?->email ?? '');
    }

    public function getSelectedBranchId(): ?int
    {
        return old('branch_id', $this->user?->branch_id ?? $this->branchUserId);
    }

    public function getSelectedRole(): string
    {
        return old('role', $this->user?->roles->first()?->name ?? '');
    }

    public function getSelectedStatus(): string
    {
        return old('status', $this->user?->status ?? 'active');
    }
}
