@props([
    'provider' => null,
])

{{-- Sección 1: Datos Básicos --}}
<h3 class="form-section-title">Datos Básicos</h3>
<div class="row g-3">
    <div class="col-md-6">
        <x-bootstrap.compact-input id="business_name" name="business_name" label="Razón Social / Nombre Comercial"
            placeholder="Ej: Computech SA" :value="old('business_name', $provider->business_name ?? '')" required />
    </div>

    <div class="col-md-6">
        <x-bootstrap.compact-input id="tax_id" name="tax_id" label="CUIT/DNI" placeholder="Ej: 30-12345678-9"
            :value="old('tax_id', $provider->tax_id ?? '')" required />
    </div>

    <div class="col-md-6">
        <x-bootstrap.compact-input id="short_name" name="short_name" label="Nombre Corto (opcional)"
            placeholder="Ej: Computech" :value="old('short_name', $provider->short_name ?? '')" />
    </div>

    <div class="col-md-6">
        <x-bootstrap.compact-input id="address" name="address" label="Dirección" placeholder="Ej: Av. Cabildo 1234"
            :value="old('address', $provider->address ?? '')" />
    </div>
</div>

<hr class="my-3">

{{-- Sección 2: Contacto --}}
<h3 class="form-section-title">Datos de Contacto</h3>
<div class="row g-3">
    <div class="col-md-4">
        <x-bootstrap.compact-input id="contact_name" name="contact_name" label="Persona de Contacto"
            placeholder="Ej: Juan Pérez" :value="old('contact_name', $provider->contact_name ?? '')" />
    </div>

    <div class="col-md-4">
        <x-bootstrap.compact-input id="email" type="email" name="email" label="Email de Contacto"
            placeholder="Ej: contacto@proveedor.com" :value="old('email', $provider->email ?? '')" />
    </div>

    <div class="col-md-4">
        <x-bootstrap.compact-input id="phone" name="phone" label="Teléfono" placeholder="Ej: +54 9 11 5555-1234"
            :value="old('phone', $provider->phone ?? '')" />
    </div>
</div>

{{-- <hr class="my-3"> --}}

{{-- Sección 3: Ubicación --}}
{{-- <h3 class="form-section-title">Ubicación</h3>
<div class="row g-3">
    
</div> --}}
