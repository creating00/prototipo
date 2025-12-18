<?php

namespace App\Enums;

enum SaleStatus: int
{
    case Paid = 1;
    case Pending = 2;
    case Cancelled = 3;

    public function label(): string
    {
        return match ($this) {
            self::Paid     => 'Pagado',
            self::Pending  => 'Pendiente',
            self::Cancelled => 'Cancelado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending   => 'badge-custom badge-custom-pastel-yellow',
            self::Paid      => 'badge-custom badge-custom-pastel-green',
            self::Cancelled => 'badge-custom badge-custom-pastel-red',
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
