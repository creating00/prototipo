<?php

namespace App\Enums;

enum SaleType: int
{
    case Sale = 1;
    case Repair = 2;

    public function label(): string
    {
        return match ($this) {
            self::Sale   => 'Venta',
            self::Repair => 'Reparación',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Sale   => 'badge-custom badge-custom-pastel-blue', // Ejemplo
            self::Repair => 'badge-custom badge-custom-pastel-orange', // Ejemplo
        };
    }

    public static function forSelect(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
