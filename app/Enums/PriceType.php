<?php

namespace App\Enums;

enum PriceType: int
{
    case PURCHASE = 1;
    case SALE = 2;
    case WHOLESALE = 3;

    public function label(): string
    {
        return match ($this) {
            self::PURCHASE => 'Compra',
            self::SALE => 'Venta',
            self::WHOLESALE => 'Mayorista',
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
