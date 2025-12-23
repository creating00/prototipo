<?php

namespace App\Services\User;

use App\Models\User;
use App\Traits\HasStatusBadge;

class UserDataTableService
{
    use HasStatusBadge;

    /**
     * Obtiene todos los usuarios y los transforma para el componente DataTable.
     */
    public function getAllUsersForDataTable(): array
    {
        $users = User::with(['branch'])
            ->orderByDesc('created_at')
            ->get();

        return $users->map(function ($user, $index) {
            return [
                'id'         => $user->id,
                'number'     => $index + 1,
                'name'       => $user->name,
                'email'      => $this->formatEmail($user->email),
                'branch'     => $user->branch->name ?? '<span class="text-muted">Sin asignar</span>',
                //'status'     => $this->formatUserStatus($user->status ?? 'active'),
                'created_at' => $user->created_at->format('d/m/Y H:i'),
            ];
        })->toArray();
    }

    /**
     * Formatea el estado del usuario usando el Trait de badges.
     */
    private function formatUserStatus(string $status): string
    {
        $label = $status === 'active' ? 'Activo' : 'Inactivo';

        // Se asume que HasStatusBadge maneja la lógica de colores según el texto o clase
        return $this->formatStatusBadge($label, $status === 'active' ? 'success' : 'danger');
    }

    /**
     * Formato simple para el email con un icono.
     */
    private function formatEmail(string $email): string
    {
        return sprintf(
            '<i class="far fa-envelope text-muted me-1"></i> %s',
            $email
        );
    }
}
