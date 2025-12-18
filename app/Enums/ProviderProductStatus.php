<?php

namespace App\Enums;

enum ProviderProductStatus: int
{
    case ACTIVE = 1;
    case INACTIVE = 2;
    case DISCONTINUED = 3;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE       => 'Activo',
            self::INACTIVE     => 'Inactivo',
            self::DISCONTINUED => 'Descontinuado',
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
