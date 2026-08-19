<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Branch;
use App\Enums\RoleLabel;

trait AuthTrait
{
    protected function currentUser(): ?User
    {
        return Auth::user();
    }

    protected function userId(): ?int
    {
        return Auth::id();
    }

    /**
     * Retorna el ID de la sucursal activa en la sesión o el del usuario como fallback.
     * Retorna null si se encuentra en modo de vista consolidada ('all').
     */
    protected function currentBranchId(): ?int
    {
        if ($this->isProvincialAdmin() && session()->has('active_branch_id')) {
            $activeId = session('active_branch_id');
            return $activeId === 'all' ? null : (int) $activeId;
        }

        return $this->currentUser()?->branch_id
            ? (int) $this->currentUser()->branch_id
            : null;
    }

    /**
     * Retorna la ID de la provincia asociada al usuario (directa o via sucursal).
     */
    protected function currentProvinceId(): ?int
    {
        $user = $this->currentUser();
        if (!$user) {
            return null;
        }

        return $user->province_id ?? $user->branch?->province_id;
    }

    /**
     * Indica si el usuario actual posee el rol de Administrador Provincial.
     */
    protected function isProvincialAdmin(): bool
    {
        $user = $this->currentUser();
        return $user ? $user->hasRole(RoleLabel::PROVINCIAL_ADMIN->value) : false;
    }

    /**
     * Indica si la sesión actual está en Modo Consolidado Provincial/Global.
     */
    protected function isConsolidatedProvincialMode(): bool
    {
        return $this->isProvincialAdmin() &&
            (session('active_branch_id') === 'all' || !session()->has('active_branch_id'));
    }

    /**
     * Indica si la sesión activa está configurada en Modo Consolidado ("Todas las sucursales").
     */
    protected function isConsolidatedMode(): bool
    {
        return session('active_branch_id') === 'all';
    }

    /**
     * Redirecciona con alerta de error si se intenta realizar mutación (ABM) en modo consolidado.
     */
    protected function denyIfConsolidatedMutation(string $fallbackRoute = 'dashboard')
    {
        if ($this->isConsolidatedMode()) {
            return redirect()
                ->route($fallbackRoute)
                ->withErrors('En modo consolidado ("Todas las sucursales") no se permite registrar o modificar en este módulo. Por favor seleccione una sucursal específica.');
        }

        return null;
    }

    /**
     * Retorna los IDs de sucursales accesibles según el rol del usuario actual.
     * - Admin Global: Todas las sucursales.
     * - Admin Provincial: Todas las sucursales de su provincia.
     * - Vendedor / Otros: Únicamente su sucursal asignada.
     */
    protected function getAccessibleBranchIds(): array
    {
        $user = $this->currentUser();
        if (!$user) {
            return [];
        }

        if ($user->hasRole('admin')) {
            return Branch::pluck('id')->toArray();
        }

        if ($this->isProvincialAdmin()) {
            $provinceId = $this->currentProvinceId();
            return $provinceId
                ? Branch::where('province_id', $provinceId)->pluck('id')->toArray()
                : [];
        }

        return $user->branch_id ? [$user->branch_id] : [];
    }

    protected function redirectIfNotAdmin(string $route)
    {
        if (!$this->currentUser()?->hasRole('admin')) {
            return redirect()->route($route);
        }

        return null;
    }
}
