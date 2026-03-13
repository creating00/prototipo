<?php

namespace App\ViewModels;

use App\Models\Promotion;
use Illuminate\Support\Collection;

class PromotionFormData
{
    public function __construct(
        public readonly ?Promotion $promotion,
        public readonly Collection $branches,
        public readonly ?int $branchUserId = null,
    ) {}

    /**
     * Determina qué sucursal debe estar seleccionada por defecto.
     */
    public function selectedBranchId(): ?int
    {
        // 1. Si estamos editando, usamos la sucursal de la promoción.
        if ($this->promotion && $this->promotion->branch_id) {
            return $this->promotion->branch_id;
        }

        // 2. Si es nueva y el usuario pertenece a una sucursal, la pre-seleccionamos.
        return $this->branchUserId;
    }

    /**
     * Devuelve los botones formateados para el frontend o un array vacío.
     */
    public function buttons(): array
    {
        return $this->promotion?->buttons ?? [];
    }
}
