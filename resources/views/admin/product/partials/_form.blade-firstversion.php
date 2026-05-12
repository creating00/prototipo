@props(['formData'])

@php
    use App\Enums\PriceType;
    use App\Enums\CurrencyType;
@endphp

@push('styles')
    <style>
        /* Estilos para el tooltip de vista previa */
        .image-preview-tooltip .tooltip-inner {
            max-width: 250px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .image-preview-tooltip .tooltip-arrow {
            display: none;
        }

        /* Estilos para la imagen en el modal */
        #modal-preview-image {
            transition: transform 0.3s ease;
        }

        #modal-preview-image:hover {
            transform: scale(1.05);
        }
    </style>
@endpush

<h3>Información Básica</h3>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        {{-- Código (Code) --}}
        <x-adminlte.input-group id="code" name="code" label="Código de Producto" placeholder="Ej: PRD-001"
            :value="old('code', $formData->product?->code ?? '')" required />
    </div>

    <div class="col-md-6">
        {{-- Nombre (Name) --}}
        <x-adminlte.input-group id="name" name="name" label="Nombre del Producto"
            placeholder="Ej: Monitor LED 27''" :value="old('name', $formData->product?->name ?? '')" required />
    </div>
</div>

<h3>Descripción e Imagen</h3>

<div class="row g-3">
    <div class="col-md-8">
        {{-- Descripción (Description) - Ocupa 8 columnas (más espacio para texto) --}}
        <div class="form-group">
            <label for="description">Descripción</label>
            <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description"
                rows="5" placeholder="Detalles y especificaciones del producto">{{ old('description', $formData->product?->description ?? '') }}</textarea>

            @error('description')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <!-- Opción 1: Subir archivo -->
        <x-adminlte.input name="imageFile" type="file" label="Subir Imagen" accept="image/*" />

        <!-- Opción 2: Pegar URL -->
        <div class="form-group">
            <label for="image_url">O pegar URL de imagen externa</label>
            <div class="input-group">
                <input type="url" name="imageUrl" id="image_url" class="form-control"
                    placeholder="https://ejemplo.com/imagen.jpg"
                    value="{{ old('imageUrl', $formData->product?->image ?? '') }}">
                <button type="button" class="btn btn-outline-secondary" onclick="previewImageModal()"
                    id="preview-button">
                    <i class="fas fa-eye"></i> Vista previa
                </button>
            </div>
        </div>

        <!-- Previsualización de imagen -->
        <div id="image-preview" class="mt-2" style="display: none;">
            <label>Vista previa:</label>
            <img id="preview-image" src="" alt="Vista previa"
                style="max-width: 100%; height: auto; border: 1px solid #ccc; padding: 5px;">
        </div>

        @if ($formData->product?->image)
            <div class="mt-3">
                <label>Imagen Actual:</label>
                <img src="{{ $formData->product?->image }}" alt="Imagen actual"
                    style="max-width: 100%; height: auto; display: block; border: 1px solid #ccc; padding: 5px;">

                <div class="form-check mt-2">
                    <input type="checkbox" name="removeImage" id="removeImage" value="1" class="form-check-input"
                        {{ old('removeImage') ? 'checked' : '' }}>
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

<hr class="my-3">

<h3>Inventario y Estado</h3>

<div class="row">
    <div class="col-md-4 align-self-end">
        {{-- Stock --}}
        <x-adminlte.input-group id="stock" name="stock" type="number" label="Stock Actual" placeholder="0"
            :value="old('stock', $formData->productBranch->stock)" min="0" />
    </div>
    <div class="col-md-4 align-self-end">
        {{-- Umbral de Stock Mínimo (Low Stock Threshold) --}}
        <x-adminlte.input-group id="low_stock_threshold" name="low_stock_threshold" type="number"
            label="Stock Mínimo de Alerta" placeholder="5" :value="old('low_stock_threshold', $formData->productBranch->low_stock_threshold)" min="0" />
    </div>

    <div class="col-md-4 align-self-end">
        {{-- Estado (Status - Select con alineación forzada) --}}
        <x-adminlte.select name="status" label="Estado del Producto" :options="$formData->statusOptions"
            placeholder="Seleccione el estado" :value="old('status', $formData->productBranch->status->value)" required />
    </div>
</div>

<hr class="my-3">

<h3>Precios y Moneda</h3>

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

<hr class="my-3">

<h3>Ubicación y Clasificación</h3>

<div class="row">
    {{-- Sucursal --}}
    <div class="col-md-6">
        <x-adminlte.select-with-action name="branch_id" label="Sucursal Principal" :options="$formData->branches->pluck('name', 'id')->toArray()"
            :value="old('branch_id', $formData->product?->branch_id ?? $formData->branchUserId)" required buttonId="btn-new-branch" />
    </div>

    {{-- Categoría --}}
    <div class="col-md-6">
        <x-adminlte.select-with-action name="category_id" label="Categoría" :options="$formData->categories->pluck('name', 'id')->toArray()" :value="old('category_id', $formData->product?->category_id)"
            buttonId="btn-new-category" />
    </div>
</div>
