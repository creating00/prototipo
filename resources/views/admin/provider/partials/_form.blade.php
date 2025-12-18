@props([
    'provider' => null,
])

{{-- Sección 1: Datos Básicos --}}
<h5 class="mb-3">Datos Básicos</h5>
<div class="row g-3">
    <div class="col-md-6">
        <x-admin-lte.input-group id="business_name" name="business_name" label="Razón Social / Nombre Comercial"
            placeholder="Ej: Computech SA" :value="old('business_name', $provider->business_name ?? '')" required />
    </div>

    <div class="col-md-6">
        <x-admin-lte.input-group id="tax_id" name="tax_id" label="CUIT/DNI" placeholder="Ej: 30-12345678-9"
            :value="old('tax_id', $provider->tax_id ?? '')" required />
    </div>

    <div class="col-md-6">
        <x-admin-lte.input-group id="short_name" name="short_name" label="Nombre Corto (opcional)"
            placeholder="Ej: Computech" :value="old('short_name', $provider->short_name ?? '')" />
    </div>
</div>

<hr class="my-4">

{{-- Sección 2: Contacto --}}
<h5 class="mb-3">Datos de Contacto</h5>
<div class="row g-3">
    <div class="col-md-6">
        <x-admin-lte.input-group id="contact_name" name="contact_name" label="Persona de Contacto"
            placeholder="Ej: Juan Pérez" :value="old('contact_name', $provider->contact_name ?? '')" />
    </div>

    <div class="col-md-6">
        <x-admin-lte.input-group id="email" type="email" name="email" label="Email de Contacto"
            placeholder="Ej: contacto@proveedor.com" :value="old('email', $provider->email ?? '')" />
    </div>

    <div class="col-md-6">
        <x-admin-lte.input-group id="phone" name="phone" label="Teléfono" placeholder="Ej: +54 9 11 5555-1234"
            :value="old('phone', $provider->phone ?? '')" />
    </div>
</div>

<hr class="my-4">

{{-- Sección 3: Ubicación --}}
<h5 class="mb-3">Ubicación</h5>
<div class="row g-3">
    <div class="col-md-12">
        <x-admin-lte.input-group id="address" name="address" label="Dirección" placeholder="Ej: Av. Cabildo 1234"
            :value="old('address', $provider->address ?? '')" />
    </div>
</div>
