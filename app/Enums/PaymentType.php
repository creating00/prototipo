<?php

namespace App\Enums;

enum PaymentType: int
{
    case Cash = 1;
    case Card = 2; // Incluye crédito y débito
    case Transfer = 3;
    case Check = 4;

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Card => 'Tarjeta',
            self::Transfer => 'Transferencia Bancaria',
            self::Check => 'Cheque',
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
