<?php
namespace App\Enums;

enum NotificationType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Created   => 'Nuevo Pedido',
            self::Updated   => 'Actualización',
            self::Cancelled => 'Cancelación',
        };
    }
}