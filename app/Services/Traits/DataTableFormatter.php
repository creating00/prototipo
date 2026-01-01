<?php

namespace App\Services\Traits;

use App\Enums\CurrencyType;

trait DataTableFormatter
{
    protected function resolveCustomerName($model): string
    {
        if (!$model->customer) {
            return '';
        }

        return match ($model->customer_type) {

            \App\Models\Client::class => (
                // Si existe el accessor, úsalo
                method_exists($model->customer, 'getDisplayNameHtmlAttribute')
                ? $model->customer->display_name_html
                : ($model->customer->full_name ?? $model->customer->document ?? '')
            ),

            \App\Models\Branch::class => $model->customer->name ?? '',

            default => '',
        };
    }

    protected function formatCurrency(float $amount, ?CurrencyType $currency = null, string $class = 'fw-bold text-success'): string
    {
        $currency = $currency ?? CurrencyType::ARS;
        return sprintf(
            '<span class="%s">%s %s</span>',
            $class,
            $currency->symbol(),
            number_format($amount, 2, ',', '.')
        );
    }

    protected function formatStatusBadge(string $statusLabel): string
    {
        $statusEnum = collect(\App\Enums\SaleStatus::cases())
            ->first(fn($case) => $case->label() === $statusLabel);

        if (!$statusEnum) {
            return "<span class=\"badge-custom badge-custom-pastel-blue\">{$statusLabel}</span>";
        }

        $class = $statusEnum->badgeClass();
        return "<span class=\"{$class}\">{$statusLabel}</span>";
    }

    /**
     * Detecta automáticamente si el modelo usa un Enum con label() y badgeClass().
     * Funciona para OrderStatus y SaleStatus sin condicionales adicionales.
     */
    protected function resolveStatus($model, array $options): string
    {
        // Caso moderno usando Enum tipado
        if (method_exists($model->status, 'label')) {

            $label = $model->status->label();

            // Maneja OrderStatus y SaleStatus automáticamente
            if (method_exists($model->status, 'badgeClass')) {
                $class = $model->status->badgeClass();
                return "<span class=\"{$class}\">{$label}</span>";
            }

            return $label;
        }

        // Caso antiguo con arrays
        if (!empty($options['statusLabels'])) {
            return $options['statusLabels'][$model->status] ?? 'Desconocido';
        }

        return 'Desconocido';
    }

    /**
     * Formato general para cualquier DataTable (Orders, Sales, etc.)
     */
    protected function formatForDataTable($model, int $index, array $options = []): array
    {
        $phone = $this->cleanPhoneNumber($model->customer?->phone);

        return [
            'id'           => $model->id,
            'number'       => $index + 1,
            'branch'       => $model->branch->name ?? '',
            'customer'     => $this->resolveCustomerName($model),
            'customer_type' => $model->customer_type,
            'total'        => $this->formatCurrency($model->total_amount),
            'status'       => $this->resolveStatus($model, $options),
            'status_raw'   => is_object($model->status) ? $model->status->value : $model->status,
            'created_at'   => $model->created_at->format('Y-m-d'),
            'phone'        => $phone,
            'whatsapp-url' => $phone ? $this->getWhatsAppLink($model, $phone) : null,
        ];
    }

    private function cleanPhoneNumber(?string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone ?? '');
    }

    private function getWhatsAppLink($model, string $phone): string
    {
        $message = urlencode($model->generateWhatsAppMessage());
        return "https://wa.me/{$phone}?text={$message}";
    }
}
