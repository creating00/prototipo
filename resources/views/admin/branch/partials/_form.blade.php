@props([
    'branch' => null,
    'provinces' => [],
])

<div class="row g-3">
    {{-- Nombre de la sede (Ocupa media fila en desktop) --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="name" name="name" label="Nombre de la Sucursal"
            placeholder="Ej: Sucursal Centro" :value="old('name', $branch->name ?? '')" required />
    </div>

    {{-- Dirección (Ocupa media fila en desktop) --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input id="address" name="address" label="Dirección" placeholder="Ej: Av. Rivadavia 1234"
            :value="old('address', $branch->address ?? '')" />
    </div>

    {{-- Provincia (Ocupa toda la fila o puedes ajustarlo) --}}
    <div class="col-md-12">
        <x-admin-lte.select name="province_id" label="Provincia" :options="$provinces->pluck('name', 'id')->toArray()"
            placeholder="Seleccione una provincia" :value="old('province_id', $branch->province_id ?? null)" required />
    </div>
</div>
