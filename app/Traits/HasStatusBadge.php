<?php

namespace App\Traits;

trait HasStatusBadge
{
    /**
     * Genera un badge HTML para un status basado en su enum.
     *
     * @param string $statusLabel Texto del status (ej: "Efectivo", "Disponible")
     * @param string $enumClass   Clase del enum (ej: \App\Enums\PaymentType::class)
     * @param string $fallbackClass Clase CSS para fallback si no se encuentra el case
     * @return string
     */
    protected function formatStatusBadge(
        string $statusLabel,
        string $enumClass,
        string $fallbackClass = 'badge-custom badge-custom-pastel-blue'
    ): string {
        // Buscar el case cuyo label coincide con el texto
        $statusEnum = collect($enumClass::cases())
            ->first(fn($case) => method_exists($case, 'label') && $case->label() === $statusLabel);

        if (!$statusEnum) {
            // Fallback: badge pastel azul si no se encuentra
            return "<span class=\"{$fallbackClass}\">{$statusLabel}</span>";
        }

        // Usar la clase definida en el enum
        $class = method_exists($statusEnum, 'badgeClass')
            ? $statusEnum->badgeClass()
            : $fallbackClass;

        return "<span class=\"{$class}\">{$statusLabel}</span>";
    }
}
