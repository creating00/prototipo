<?php

namespace App\Enums;

enum CurrencyType: int
{
    case ARS = 1;
    case USD = 2;
    // Podrían añadir otros en el futuro: case EUR = 3;

    public function code(): string
    {
        return match ($this) {
            self::ARS => 'ARS',
            self::USD => 'USD',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::ARS => '$', // Pesos Argentinos usan el símbolo de dólar
            self::USD => 'U$D', // Se suele diferenciar en Argentina como "U$D"
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ARS => 'success',
            self::USD => 'primary',
        };
    }

    public static function forSelect(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            // Usamos el código de 3 letras como etiqueta para ser explícitos
            $options[$case->value] = $case->code();
        }
        return $options;
    }
}
