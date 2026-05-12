@extends('layouts.app')

@section('page-title', 'Editar Producto')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 100%;">
            <x-adminlte.alert-manager />
            <x-adminlte.form action="{{ route('web.products.update', $formData->product) }}" method="POST"
                title="Editar Producto: {{ $formData->product->name }}" submit-text="Actualizar Producto"
                submitting-text="Actualizando producto..." enctype="multipart/form-data" {{-- ¡Necesario para subir imágenes! --}}>
                @method('PUT')
                @include('admin.product.partials._form', [
                    'formData' => $formData,
                ])
            </x-adminlte.form>
        </div>
    </div>
    <x-adminlte.toast-container>
        <x-adminlte.toast id="toastWarning" color="warning" title="Eliminar imagen" time="Ahora" icon="fas fa-trash">
            <div class="toast-body">
                <i class='fas fa-exclamation-triangle'></i> <strong>¡Atención!</strong><br>
                La imagen actual será eliminada al guardar. Los campos de imagen han sido deshabilitados.
            </div>
        </x-adminlte.toast>
    </x-adminlte.toast-container>

    @include('admin.category.partials._modal-create')
    @include('admin.branch.partials._modal-create')
    @include('admin.product.partials._image-modal-preview')
    @include('admin.provider.partials._modal-create')
@endsection

@push('scripts')
    <script>
        // Inyectamos los datos para que el Manager los tome al iniciar
        window.ProductFormData = {
            existingProviders: @json($formData->product->providers)
        };
    </script>
    @vite('resources/js/modules/products/product-form.js')
@endpush
