<?php

namespace App\Services\User;

use App\Models\User;
use App\Traits\HasStatusBadge;
use App\Enums\RoleLabel;

class UserDataTableService
{
    use HasStatusBadge;

    /**
     * Obtiene todos los usuarios y los transforma para el componente DataTable.
     */
    public function getAllUsersForDataTable(): array
    {
        $users = User::with(['branch.province', 'province', 'roles'])
            ->whereNotIn('email', ['ecommerce@system.com', 'soporte@creatingsoft.net'])
            ->orderByDesc('created_at')
            ->get();

        return $users->map(function ($user, $index) {
            $branchDisplay = '<span class="text-muted">Sin asignar</span>';

            if ($user->hasRole('admin')) {
                $branchDisplay = '<span class="badge bg-primary">Todas las sucursales</span>';
            } elseif ($user->hasRole(RoleLabel::PROVINCIAL_ADMIN->value)) {
                $provName = $user->province?->name ?? $user->branch?->province?->name ?? 'Córdoba';
                $branchDisplay = sprintf('<span class="badge bg-info text-dark"><i class="bi bi-geo-alt me-1"></i>Provincial (%s)</span>', e($provName));
            } elseif ($user->branch) {
                $branchDisplay = e($user->branch->name);
            }

            return [
                'id'         => $user->id,
                'number'     => $index + 1,
                'name'       => $user->name,
                'email'      => $this->formatEmail($user->email),
                'branch'     => $branchDisplay,
                'created_at' => $user->created_at?->format('d/m/Y H:i') ?? '-',
            ];
        })->toArray();
    }

    /**
     * Formatea el estado del usuario usando el Trait de badges.
     */
    private function formatUserStatus(string $status): string
    {
        $label = $status === 'active' ? 'Activo' : 'Inactivo';

        return $this->formatStatusBadge($label, $status === 'active' ? 'success' : 'danger');
    }

    /**
     * Formato simple para el email con un icono.
     */
    private function formatEmail(string $email): string
    {
        return sprintf(
            '<i class="far fa-envelope text-muted me-1"></i> %s',
            e($email)
        );
    }
}
