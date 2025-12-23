@props(['provider', 'products', 'providerProduct' => null])

<input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">

<div class="row g-3">
    {{-- Producto --}}
    <div class="col-md-12">
        <x-admin-lte.select name="product_id" label="Producto" :options="$products->pluck('name', 'id')->toArray()" :value="old('product_id', $providerProduct->product_id ?? null)"
            placeholder="Seleccione un producto" required />
    </div>

    {{-- Código del proveedor --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input name="provider_code" label="Código del proveedor"
            placeholder="Código interno del proveedor"
            value="{{ old('provider_code', $providerProduct->provider_code ?? '') }}" />
    </div>

    {{-- Lead time --}}
    <div class="col-md-6">
        <x-bootstrap.compact-input name="lead_time_days" type="number" label="Lead time (días)" placeholder="Ej: 7"
            min="0" value="{{ old('lead_time_days', $providerProduct->lead_time_days ?? '') }}" />
    </div>

    {{-- Status --}}
    <div class="col-md-6">
        <x-admin-lte.select name="status" label="Estado" :options="\App\Enums\ProviderProductStatus::forSelect()" :value="old('status', $providerProduct?->status?->value ?? \App\Enums\ProviderProductStatus::ACTIVE->value)" required />
    </div>

</div>
