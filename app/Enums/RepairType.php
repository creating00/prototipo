<?php

namespace App\Enums;

enum RepairType: int
{
    case Module = 1;
    case Battery = 2;
    case ChargingPort = 3;
    case Glass = 4;
    case MicroSoldering = 5;
    case Other = 6;

    public function label(): string
    {
        return match ($this) {
            self::Module         => 'Cambio de Modulo',
            self::Battery        => 'Cambio de Batería',
            self::ChargingPort   => 'Cambio de pin de carga/Placa de carga',
            self::Glass          => 'Cambio de glass',
            self::MicroSoldering => 'Microsoldadura',
            self::Other          => 'Otro',
        };
    }

    public function categoryId(): ?int
    {
        return match ($this) {
            self::Module       => 1, // ID de la categoría Módulos
            self::Battery      => 2, // ID de la categoría Baterías
            self::ChargingPort => 3, // ID de la categoría Pines de Carga
            self::Glass        => 4, // ID de la categoría Glass
            default            => null,
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
