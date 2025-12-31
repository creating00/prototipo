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
            {{-- 
                Wrapper para emular 'Floating Label' en el componente Select.
                Requiere label="" en el componente para evitar duplicidad de etiquetas.
            --}}
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
    <style>
        .compact-select-wrapper {
            position: relative;
            /* Alineamos el margen inferior con tus .compact-input-wrapper */
            margin-bottom: 0.4rem;
            padding-top: 0.25rem;
        }

        /* Simula la etiqueta flotante superior (Clon de .compact-input-label) */
        .compact-select-label {
            position: absolute;
            left: 0.75rem;
            top: -0.25rem;
            /* Ajustado para nivelar con tus otros inputs */
            font-size: 0.7rem;
            font-weight: 500;
            color: #4b5563;
            background-color: #fff;
            padding: 0 0.3rem;
            z-index: 30;
            pointer-events: none;
            letter-spacing: 0.025em;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }

        /* Reseteo del componente base */
        .compact-select-wrapper .mb-3 {
            margin-bottom: 0 !important;
        }

        /* Ajuste estructural de Choices.js */
        .compact-select-wrapper .choices__inner {
            /* Altura idéntica a .compact-input: calc(2.8rem + 2px) */
            min-height: calc(2.8rem + 2px) !important;
            background-color: #fff !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.65rem 0.75rem 0.4rem !important;
            /* Mismo padding que .compact-input */
            display: flex;
            align-items: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Simulación de Focus (Mismo color que .compact-input:focus) */
        .compact-select-wrapper .choices.is-focused .choices__inner {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        /* Ajuste de la flecha de Choices para que no quede descentrada */
        .compact-select-wrapper .choices__list--single {
            padding: 0 !important;
            font-size: 0.875rem;
        }
    </style>
@endpush
