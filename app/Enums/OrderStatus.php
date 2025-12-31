<?php

namespace App\Enums;

enum OrderStatus: int
{
    case Draft = 0;
    case Pending = 1;
    case Confirmed = 2;
    case Cancelled = 3;
    case ConvertedToSale = 4;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Pending         => 'Pendiente',
            self::Confirmed => 'Confirmado',
            self::Cancelled => 'Cancelado',
            self::ConvertedToSale => 'Convertido en Venta',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft           => 'badge-custom badge-custom-pastel-blue',
            self::Pending         => 'badge-custom badge-custom-pastel-yellow',
            self::Confirmed       => 'badge-custom badge-custom-pastel-green',
            self::Cancelled       => 'badge-custom badge-custom-pastel-red',
            self::ConvertedToSale => 'badge-custom badge-custom-pastel-purple',
        };
    }

    public static function forInternalOrder(): array
    {
        return [
            self::Draft->value   => self::Draft->label(),
            self::Pending->value => self::Pending->label(),
        ];
    }

    public static function forSale(): array
    {
        return [
            self::Draft->value     => self::Draft->label(),
            self::Pending->value   => self::Pending->label(),
            self::Confirmed->value => self::Confirmed->label(),
        ];
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
