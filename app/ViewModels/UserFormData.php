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
        return \App\Enums\RoleLabel::forSelect();
    }

    public function getBranchOptions(): array
    {
        $options = ['' => 'Sin sucursal asignada (Todas las sucursales)'];
        foreach ($this->branches as $branch) {
            $options[$branch->id] = $branch->name;
        }
        return $options;
    }

    public function getProvinceOptions(): array
    {
        $options = ['' => 'Sin provincia específica'];
        foreach ($this->provinces as $province) {
            $options[$province->id] = $province->name;
        }
        return $options;
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

    public function getSelectedProvinceId(): ?int
    {
        return old('province_id', $this->user?->province_id);
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
