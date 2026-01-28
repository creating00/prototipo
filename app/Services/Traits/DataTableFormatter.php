<?php

namespace App\Services\Traits;

use App\Enums\CurrencyType;
use App\Models\Sale;

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

    protected function formatCurrency(float $amount, ?CurrencyType $currency = null, string $class = 'fw-bold'): string
    {
        $currency = $currency ?? CurrencyType::ARS;

        // Usamos el color definido en el Enum: text-success o text-primary
        $colorClass = "text-" . $currency->color();

        return sprintf(
            '<span class="%s %s">%s %s</span>',
            $class,
            $colorClass,
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
        $customerName = $this->resolveCustomerName($model);

        // Ya viene casteado como array gracias a $casts
        $totals = $model->totals ?? [];

        // Totales numéricos (para JS)
        $totalArs = $totals[CurrencyType::ARS->value] ?? 0;
        $totalUsd = $totals[CurrencyType::USD->value] ?? 0;

        // Totales formateados (para mostrar)
        $formattedTotals = collect($totals)
            ->map(function ($amount, $currencyId) {
                $currency = CurrencyType::tryFrom((int) $currencyId);
                return $this->formatCurrency((float) $amount, $currency);
            })
            ->implode('<br>');

        $requiresInvoiceHtml = $model->requires_invoice
            ? '<span class="badge bg-success">Sí</span>'
            : '<span class="badge bg-secondary">No</span>';

        $payment = $model->payments->first();

        $paymentTypeHtml = $payment
            ? sprintf(
                '<span class="%s">%s</span>',
                $payment->payment_type->badgeClass(),
                $payment->payment_type->label()
            )
            : '-';

        return [
            'id'            => $model->id,
            'number'        => $index + 1,
            'branch'        => $model->branch->name ?? '',
            'customer'      => $customerName,
            'customer_type' => $model->customer_type,
            'payment_type'     => $paymentTypeHtml,
            'total' => $formattedTotals ?: $this->formatCurrency(0),
            'requires_invoice' => $requiresInvoiceHtml,
            'requires_invoice_raw' => $model->requires_invoice,
            'status'           => $this->resolveStatus($model, $options),
            'status_raw'       => is_object($model->status) ? $model->status->value : $model->status,
            'created_at'       => $model->created_at->format('Y-m-d'),
            'phone'            => $phone,
            'whatsapp-url'     => $phone ? $this->getWhatsAppLink($model, $phone) : null,

            // opcional (solo si lo usás en JS en otro lado)
            'total_ars' => $totalArs,
            'total_usd' => $totalUsd,
            'totals_json' => json_encode($totals),

            'customer_name_raw' => $customerName,
        ];
    }

    protected function formatSaleForDataTable(Sale $sale, int $index): array
    {
        // Obtenemos el formateo base (esto llama a formatForDataTable)
        $row = $this->formatForDataTable($sale, $index);

        // Formateamos el "Tipo" con el atributo data-search para el JS
        $saleTypeHtml = $sale->sale_type
            ? sprintf(
                '<span class="%s" data-search="%s">%s</span>',
                $sale->sale_type->badgeClass(),
                $sale->sale_type->value, // Usamos el ID (1, 2...)
                $sale->sale_type->label()
            )
            : '';

        // Formateamos el "Pago" también con data-search para mayor precisión
        $payment = $sale->payments->first();
        $paymentTypeHtml = $payment
            ? sprintf(
                '<span class="%s" data-search="%s">%s</span>',
                $payment->payment_type->badgeClass(),
                $payment->payment_type->value, // Usamos el ID (1, 2, 3...)
                $payment->payment_type->label()
            )
            : '-';

        // Actualizamos los valores en el array
        $row['sale_type'] = $saleTypeHtml;
        $row['payment_type'] = $paymentTypeHtml;

        // Insertar "sale_type" después de "customer" (índice 4)
        return array_merge(
            array_slice($row, 0, 4, true),
            ['sale_type' => $saleTypeHtml],
            array_slice($row, 4, null, true)
        );
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
