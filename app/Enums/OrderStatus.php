<?php

namespace App\Enums;

enum OrderStatus: int
{
    case Draft = 0;
    case Confirmed = 1;
    case Cancelled = 2;
    case ConvertedToSale = 3;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Confirmed => 'Confirmado',
            self::Cancelled => 'Cancelado',
            self::ConvertedToSale => 'Convertido en Venta',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft            => 'badge-custom badge-custom-pastel-blue',
            self::Confirmed        => 'badge-custom badge-custom-pastel-green',
            self::Cancelled        => 'badge-custom badge-custom-pastel-red',
            self::ConvertedToSale  => 'badge-custom badge-custom-pastel-purple',
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
