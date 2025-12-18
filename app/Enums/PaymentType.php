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
            self::Transfer => 'Transferencia',
            self::Check => 'Cheque',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Cash => 'badge-custom badge-custom-green',
            self::Card => 'badge-custom badge-custom-ocean', // puedes usar sky/ocean/cyan 
            self::Transfer => 'badge-custom badge-custom-emerald',
            self::Check => 'badge-custom badge-custom-yellow',
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
