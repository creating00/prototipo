@props(['formData'])

@php
    use App\Enums\PriceType;
    use App\Enums\CurrencyType;
@endphp

@push('styles')
    @vite('resources/css/modules/products/products-styles.css')
@endpush
<div class="form-section">
    <h3 class="form-section-title">Información Básica</h3>

    <div class="row g-3 align-items-center">

        {{-- Código --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="code" name="code" label="Código de Producto" placeholder="Ej: PRD-001"
                value="{{ old('code', $formData->product?->code ?? '') }}" required />
        </div>

        {{-- Nombre --}}
        <div class="col-md-6">
            <x-bootstrap.compact-input id="name" name="name" label="Nombre del Producto"
                placeholder="Ej: Monitor LED 27''" value="{{ old('name', $formData->product?->name ?? '') }}"
                required />
        </div>

        {{-- Descripción --}}
        <div class="col-md-8">
            <x-bootstrap.compact-text-area name="description" label="Descripción"
                placeholder="Detalles y especificaciones del producto"
                value="{{ old('description', $formData->product?->description ?? '') }}" rows="4" />
        </div>

        {{-- Imagen --}}
        <div class="col-md-4 compact-media">

            {{-- Subir archivo --}}
            <x-bootstrap.compact-file-input name="imageFile" label="Imagen" accept="image/*" />

            {{-- URL externa --}}
            <div class="form-group">
                <label for="image_url">URL de imagen externa</label>
                <div class="input-group">
                    <input type="url" name="imageUrl" id="image_url" class="form-control"
                        placeholder="https://ejemplo.com/imagen.jpg"
                        value="{{ old('imageUrl', $formData->product?->image ?? '') }}">
                    <button type="button" class="btn btn-outline-secondary" id="preview-button">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            {{-- Imagen actual --}}
            @if ($formData->product?->image)
                <div class="mt-2">
                    <img src="{{ $formData->product?->image }}" alt="Imagen actual"
                        class="img-fluid rounded border p-1">

                    <div class="form-check mt-2">
                        <input type="checkbox" name="removeImage" id="removeImage" value="1"
                            class="form-check-input" {{ old('removeImage') ? 'checked' : '' }}>
                        <label for="removeImage" class="form-check-label text-danger">
                            <i class="fas fa-trash"></i> Eliminar imagen actual
                        </label>
                    </div>

                    <small class="form-text text-muted">
                        Deja ambos campos vacíos para mantener la imagen actual.
                    </small>
                </div>
            @endif
        </div>
    </div>
</div>

<hr class="my-3">

<div class="form-section">

    <h3 class="form-section-title">Inventario y Estado</h3>

    <div class="row g-3 ">
        <div class="col-md-4">
            {{-- Stock --}}
            <x-bootstrap.compact-input id="stock" name="stock" type="number" label="Stock Actual" placeholder="0"
                value="{{ old('stock', $formData->productBranch->stock ?? '') }}" min="0" />
        </div>

        <div class="col-md-4">
            {{-- Umbral de Stock Mínimo (Low Stock Threshold) --}}
            <x-bootstrap.compact-input id="low_stock_threshold" name="low_stock_threshold" type="number"
                label="Stock Mínimo de Alerta" placeholder="5"
                value="{{ old('low_stock_threshold', $formData->productBranch->low_stock_threshold ?? '') }}"
                min="0" />
        </div>

        <div class="col-md-4">
            <div class="compact-select-wrapper">
                <label class="compact-select-label">
                    Estado <span class="text-danger">*</span>
                </label>
                {{-- Estado (Status - Select con alineación forzada) placeholder="Seleccione el estado" --}}
                <x-adminlte.select name="status" label="" :options="$formData->statusOptions" :value="old('status', $formData->productBranch->status->value)" :showPlaceholder="false"
                    required />
            </div>
        </div>
    </div>
</div>

<div class="form-section">
    <hr class="my-3">

    <h3 class="form-section-title">Precios y Moneda</h3>

    {{-- Moneda (Currency - Select se mantiene) --}}
    <div class="row">
        <div class="col-md-4">
            <x-currency-price-input name="purchase_price" label="Precio de Compra (Costo)" :amount-value="old('purchase_price.amount', $formData->price(PriceType::PURCHASE->value))"
                :currency-value="old('purchase_price.currency', $formData->currency(PriceType::PURCHASE->value))" :currency-options="$formData->currencyOptions" />
        </div>
        <div class="col-md-4">
            <x-currency-price-input name="sale_price" label="Precio de Venta (Minorista)" :amount-value="old('sale_price.amount', $formData->price(PriceType::SALE->value))"
                :currency-value="old('sale_price.currency', $formData->currency(PriceType::SALE->value))" :currency-options="$formData->currencyOptions" />
        </div>

        <div class="col-md-4">
            <x-currency-price-input name="wholesale_price" label="Precio Mayorista" :amount-value="old('wholesale_price.amount', $formData->price(PriceType::WHOLESALE->value))" :currency-value="old('wholesale_price.currency', $formData->currency(PriceType::WHOLESALE->value))"
                :currency-options="$formData->currencyOptions" :required="false" />
        </div>
    </div>
</div>

<div>
    <hr class="my-3">

    <h3 class="form-section-title">Ubicación y Clasificación</h3>

    <div class="row align-items-center">
        {{-- Sucursal --}}
        <div class="col-md-6">
            @if ($formData->isAdmin)
                <x-adminlte.select-with-action name="branch_id" label="Sucursal Principal" :options="$formData->branches->pluck('name', 'id')->toArray()"
                    :value="old('branch_id', $formData->product?->branch_id ?? $formData->branchUserId)" required buttonId="btn-new-branch" />
            @else
                <x-bootstrap.select name="branch_id" label="Sucursal Asignada" :options="$formData->branches->pluck('name', 'id')->toArray()" :selected="old('branch_id', $formData->branchUserId)"
                    required readonly />
                <input type="hidden" name="branch_id" value="{{ $formData->branchUserId }}">
            @endif
        </div>

        {{-- Categoría --}}
        <div class="col-md-6">
            <x-adminlte.select-with-action name="category_id" label="Categoría" :options="$formData->categories->pluck('name', 'id')->toArray()" :value="old('category_id', $formData->product?->category_id)"
                buttonId="btn-new-category" />
        </div>
    </div>
</div>
