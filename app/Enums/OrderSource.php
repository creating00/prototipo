<?php

namespace App\Enums;

enum OrderSource: int
{
    case Backoffice = 1;
    case Ecommerce = 2;
    case Manual = 3;

    public function label(): string
    {
        return match ($this) {
            self::Backoffice => 'Backoffice',
            self::Ecommerce  => 'E-commerce',
            self::Manual     => 'Pedido Manual',
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
