@props(['formData'])

@push('styles')
    @vite('resources/css/modules/products/products-styles.css')
    <style>
        .switch-alignment {
            margin-top: 2rem;
        }
    </style>
@endpush

<div class="row">
    <div class="col-md-12">
        <h3>Información de la Promoción</h3>
        <hr>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-12">
        <x-bootstrap.compact-input id="title" name="title" label="Título Principal" placeholder="Ej: COMPRA AHORA"
            :value="old('title', $formData->promotion?->title)" required />
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <x-bootstrap.compact-input id="subtitle" name="subtitle" label="Subtítulo"
            placeholder="Ej: Productos de calidad..." :value="old('subtitle', $formData->promotion?->subtitle)" />
    </div>

    <div class="col-md-6">
        <x-bootstrap.compact-input id="label" name="label" label="Etiqueta Inferior"
            placeholder="Ej: ¡Más de 10,000 productos!" :value="old('label', $formData->promotion?->label)" />
    </div>
</div>

<div class="row g-3 align-items-center">
    <div class="col-md-6 offset-md-6 d-flex justify-content-end switch-alignment">
        <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                {{ old('is_active', $formData->promotion?->is_active ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label fw-bold" for="is_active">
                Estado: <span class="text-muted">Activar o desactivar esta promoción</span>
            </label>
        </div>
    </div>
</div>

{{-- Campos Técnicos Ocultos --}}
<input type="hidden" name="branch_id" value="{{ old('branch_id', $formData->selectedBranchId()) }}">
<input type="hidden" name="order" value="{{ old('order', $formData->promotion?->order ?? 0) }}">
{{-- 
    SECCIÓN DE BOTONES (Comentada para implementación futura con JS)
    <div id="buttons-section" class="mt-4 border-top pt-3">
        <h4>Botones de Acción</h4>
        ...
    </div>
--}}
