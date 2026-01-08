{{-- 
    Partial: Formulario de Descuentos
    Ubicación: resources/views/admin/discount/partials/_form.blade.php
    
--}}

@props(['formData'])

@php
    use App\Enums\DiscountType;
@endphp

<div class="form-section">
    <h3 class="form-section-title">Configuración del Descuento</h3>

    <div class="row g-3">
        {{-- Bloque 1: Identificación y Tipo --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="name" name="name" label="Nombre del Descuento"
                placeholder="Ej: Descuento Estacional" value="{{ old('name', $formData->discount?->name ?? '') }}"
                required />
        </div>

        <div class="col-md-6">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">
                    Tipo de Aplicación <span class="text-danger">*</span>
                </label>
                <x-adminlte.select name="type" id="type" label="" :options="DiscountType::forSelect()" :value="old('type', $formData->discount?->type?->value ?? '')"
                    :showPlaceholder="false" required />
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2 align-items-center">
        {{-- Bloque 2: Valores y Reglas --}}
        <div class="col-md-4">
            <x-bootstrap.compact-input id="value" name="value" type="number" step="0.01"
                label="Valor / Valor porcentual" placeholder="0.00"
                value="{{ old('value', $formData->discount?->value ?? '') }}" required />
        </div>

        <div class="col-md-4" id="max-amount-group">
            <x-bootstrap.compact-input id="max_amount" name="max_amount" type="number" step="0.01"
                label="Tope Máximo (Opcional)" placeholder="Ej: 500.00"
                value="{{ old('max_amount', $formData->discount?->max_amount ?? '') }}" />
        </div>

        {{-- Bloque 3: Estado del Registro --}}
        <div class="col-md-4">
            <div class="form-check form-switch ms-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input"
                    {{ old('is_active', $formData->discount?->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label ms-2" for="is_active">
                    HABILITADO
                </label>
            </div>
        </div>
    </div>
</div>

@push('styles')
    @vite('resources/css/modules/products/products-styles.css')
@endpush
