@props([
    'client' => null,
    'branch_id' => null,
])

<input type="hidden" name="branch_id" value="{{ $branch_id ?? ($client->branch_id ?? '') }}">

{{-- Sección 1: Datos Personales --}}
<h3 class="form-section-title">Datos Personales</h3>
<div class="row g-3">
    <div class="col-md-6">
        <x-bootstrap.compact-input id="full_name" name="full_name" label="Nombre Completo" placeholder="Ej: Juan Pérez"
            value="{{ old('full_name', $client->full_name ?? '') }}" required />
    </div>

    <div class="col-md-6">
        <x-bootstrap.compact-input id="document" name="document" label="Documento de Identidad"
            placeholder="Ej: 12345678" value="{{ old('document', $client->document ?? '') }}" required />
    </div>
</div>

<hr class="my-3">

{{-- Sección 2: Contacto --}}
<h3 class="form-section-title">Datos de Contacto</h3>
<div class="row g-3">
    <div class="col-md-6">
        <x-bootstrap.compact-input id="email" type="email" name="email" label="Email"
            placeholder="Ej: cliente@email.com" value="{{ old('email', $client->email ?? '') }}" />
    </div>

    <div class="col-md-6">
        <x-bootstrap.compact-input id="phone" name="phone" label="Teléfono" placeholder="Ej: +54 9 11 5555-1234"
            value="{{ old('phone', $client->phone ?? '') }}" required />
    </div>
</div>

<hr class="my-3">

{{-- Sección 3: Ubicación (opcional) --}}
<h3 class="form-section-title">Ubicación (opcional)</h3>
<div class="row g-3">
    <div class="col-md-12">
        <x-bootstrap.compact-text-area id="address" name="address" label="Dirección"
            placeholder="Ej: Av. Cabildo 1234, Buenos Aires" :value="old('address', $client->address ?? '')" rows="3" />
    </div>
</div>
