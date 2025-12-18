@props([
    'branch' => null,
    'provinces' => [],
])

{{-- Nombre de la sede --}}
<x-admin-lte.input name="name" label="Nombre de la Sucursal" placeholder="Ej: Sucursal Centro" :value="old('name', $branch->name ?? '')"
    required />

{{-- Dirección --}}
<x-admin-lte.input name="address" label="Dirección" placeholder="Ej: Av. Rivadavia 1234" :value="old('address', $branch->address ?? '')" />

{{-- Provincia --}}
<x-admin-lte.select name="province_id" label="Provincia" :options="$provinces->pluck('name', 'id')->toArray()" placeholder="Seleccione una provincia"
    :value="old('province_id', $branch->province_id ?? null)" required />
