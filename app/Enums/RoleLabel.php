<?php

namespace App\Enums;

enum RoleLabel: string
{
    case ADMIN  = 'admin';
    case EDITOR = 'editor';
    case SELLER = 'seller';

    /**
     * Label visible en la UI
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN  => 'Administrador',
            self::EDITOR => 'Editor',
            self::SELLER => 'Vendedor',
        };
    }

    /**
     * Mapa para selects (value => label)
     */
    public static function forSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $role) => [
                $role->value => $role->label(),
            ])
            ->toArray();
    }

    /**
     * Resolver label desde string (fallback seguro)
     */
    public static function labelFrom(string $roleName): string
    {
        return self::tryFrom($roleName)?->label()
            ?? ucfirst($roleName);
    }
}
