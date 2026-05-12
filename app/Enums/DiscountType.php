<?php

namespace App\Enums;

enum DiscountType: int
{
    case Fixed = 1;
    case Percentage = 2;

    public function label(): string
    {
        return match ($this) {
            self::Fixed      => 'Monto fijo',
            self::Percentage => 'Porcentaje',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Fixed      => '$',
            self::Percentage => '%',
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
