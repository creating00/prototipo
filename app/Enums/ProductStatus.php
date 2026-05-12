<?php

namespace App\Enums;

enum ProductStatus: int // 'int' es el tipo base (TINYINT)
{
    case Available = 1;
    case OutOfStock = 2;
    case Discontinued = 3;
    case LowStock = 4;

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::OutOfStock => 'Sin Stock',
            self::Discontinued => 'Descontinuado',
            self::LowStock => 'Stock Bajo',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'badge-custom badge-custom-green',
            self::OutOfStock => 'badge-custom badge-custom-red',
            self::Discontinued => 'badge-custom badge-custom-gray',
            self::LowStock => 'badge-custom badge-custom-pastel-yellow',
        };
    }

    public function isSellable(): bool
    {
        return in_array($this, [
            self::Available,
            self::LowStock,
        ], true);
    }

    /**
     * Devuelve un array con el valor (int) como clave y la etiqueta (string) como valor,
     * útil para campos select/dropdowns.
     * Ejemplo: [1 => 'Disponible', 2 => 'Sin Stock', 3 => 'Descontinuado']
     */
    public static function forSelect(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
