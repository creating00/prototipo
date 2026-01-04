<?php

namespace App\Enums;

enum ProviderOrderStatus: int
{
    case DRAFT     = 1;
    case SENT      = 2;
    case PARTIAL   = 3;
    case RECEIVED  = 4;
    case CANCELLED = 5;
    case PENDING = 6;

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Borrador',
            self::SENT      => 'Enviado',
            self::PARTIAL   => 'Recibido Parcial',
            self::RECEIVED  => 'Recibido',
            self::CANCELLED => 'Cancelado',
            self::PENDING => 'Pendiente',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT     => 'badge-custom badge-custom-pastel-blue',
            self::PENDING   => 'badge-custom badge-custom-pastel-yellow',
            self::SENT      => 'badge-custom badge-custom-pastel-blue',
            self::PARTIAL   => 'badge-custom badge-custom-pastel-orange',
            self::RECEIVED  => 'badge-custom badge-custom-pastel-green',
            self::CANCELLED => 'badge-custom badge-custom-pastel-red',
        };
    }

    public static function forSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($c) => [$c->value => $c->label()])
            ->toArray();
    }
}
