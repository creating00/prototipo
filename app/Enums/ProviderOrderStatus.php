<?php

namespace App\Enums;

enum ProviderOrderStatus: int
{
    case DRAFT     = 1;
    case SENT      = 2;
    case PARTIAL   = 3;
    case RECEIVED  = 4;
    case CANCELLED = 5;

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Borrador',
            self::SENT      => 'Enviado',
            self::PARTIAL   => 'Recibido Parcial',
            self::RECEIVED  => 'Recibido',
            self::CANCELLED => 'Cancelado',
        };
    }

    public static function forSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($c) => [$c->value => $c->label()])
            ->toArray();
    }
}
