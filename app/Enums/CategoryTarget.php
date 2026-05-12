<?php

namespace App\Enums;

enum CategoryTarget: int
{
    case Product = 1;
    case Technician = 2;
    case None = 3;

    public function label(): string
    {
        return match ($this) {
            self::Product    => 'Producto',
            self::Technician => 'Técnico',
            self::None       => 'Ninguno',
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
