@props([
    'branch' => null,
    'provinces' => [],
])

@push('styles')
    @vite('resources/css/modules/branches/branches-styles.css')
@endpush

<div class="row g-3">
    {{-- Nombre de la sede (Ocupa media fila en desktop) --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="name" name="name" label="Nombre de la Sucursal"
            placeholder="Ej: Sucursal Centro" :value="old('name', $branch->name ?? '')" required />
    </div>

    <div class="col-md-6">
        <x-bootstrap.compact-input id="phone" name="phone" label="Teléfono" placeholder="Ej: 3888305132"
            :value="old('phone', $branch->phone ?? '')" />
    </div>

    {{-- Dirección (Ocupa media fila en desktop) --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="address" name="address" label="Dirección" placeholder="Ej: Av. Rivadavia 1234"
            :value="old('address', $branch->address ?? '')" />
    </div>

    <div class="col-md-6 compact-select-wrapper"">
        <label class="compact-select-label">
            Provincia <span class="text-danger">*</span>
        </label>
        <x-adminlte.select name="province_id" label="" :options="$provinces->pluck('name', 'id')->toArray()" placeholder="Seleccione una provincia"
            :value="old('province_id', $branch->province_id ?? null)" required />
    </div>
</div>
