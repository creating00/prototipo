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

@can('manageCatalogUrls', \App\Models\Provider::class)
<div class="mb-3">
    <x-bootstrap.compact-input id="catalog_url" type="url" name="catalog_url" label="URL principal del catálogo web (opcional)"
        placeholder="https://proveedor.com/catalogo" :value="old('catalog_url', $provider->catalog_url ?? '')" />
    <label class="form-label mt-3" for="catalog_urls_text">URLs adicionales de categorías</label>
    @php($additionalUrlsText = old('catalog_urls_text', implode("\n", $provider?->catalog_urls ?? [])))
    <textarea id="catalog_urls_text" name="catalog_urls_text" rows="5" maxlength="41000"
        class="form-control {{ $errors->has('catalog_urls') || $errors->has('catalog_urls.*') || $errors->has('catalog_urls_text') ? 'is-invalid' : '' }}"
        placeholder="https://proveedor.com/categorias/modulo/&#10;https://proveedor.com/categorias/bateria/"
        aria-describedby="catalog-urls-help">{{ is_string($additionalUrlsText) ? $additionalUrlsText : '' }}</textarea>
    @foreach($errors->get('catalog_urls*') as $messages)
        @foreach($messages as $message)<div class="invalid-feedback d-block">{{ $message }}</div>@endforeach
    @endforeach
    <small id="catalog-urls-help" class="text-body-secondary">Una URL por línea, hasta 20 en total con la principal. Cada categoría se consulta y procesa por separado. Los productos procesados de todas las URLs aparecen agrupados por proveedor al comparar. Guardar una URL no garantiza extracción completa.</small>
</div>
@endcan

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
