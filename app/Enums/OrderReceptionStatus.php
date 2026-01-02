<?php

namespace App\Enums;

enum OrderReceptionStatus: int
{
    case Received = 1;
    case ReceivedWithIssues = 2;
    case Rejected = 3;

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibido',
            self::ReceivedWithIssues => 'Recibido con Observaciones',
            self::Rejected => 'Rechazado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Received           => 'badge-custom badge-custom-pastel-green',
            self::ReceivedWithIssues => 'badge-custom badge-custom-pastel-yellow',
            self::Rejected           => 'badge-custom badge-custom-pastel-red',
        };
    }
}
