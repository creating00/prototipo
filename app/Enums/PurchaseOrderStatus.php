<?php

namespace App\Enums;

enum PurchaseOrderStatus: int
{
    case DRAFT = 1;
    case PENDING = 2;
    case APPROVED = 3;
    case PARTIALLY_RECEIVED = 4;
    case RECEIVED = 5;
    case CANCELLED = 6;

    public function label(): string
    {
        return match ($this) {
            self::DRAFT              => 'Borrador',
            self::PENDING            => 'Pendiente',
            self::APPROVED           => 'Aprobada',
            self::PARTIALLY_RECEIVED => 'Recepción parcial',
            self::RECEIVED           => 'Recibida',
            self::CANCELLED          => 'Cancelada',
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
