@extends('layouts.app')

@section('page-title', 'Crear Producto')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12" style="max-width: 100%;">
            <x-adminlte.alert-manager />
            <x-adminlte.form action="{{ route('web.products.store') }}" method="POST" title="Crear Nuevo Producto"
                submit-text="Guardar Producto" submitting-text="Creando producto..." enctype="multipart/form-data">
                @include('admin.product.partials._form', [
                    'formData' => $formData,
                ])
            </x-adminlte.form>
        </div>
    </div>

    @include('admin.category.partials._modal-create')
    @include('admin.branch.partials._modal-create')
    @include('admin.product.partials._image-modal-preview')
    @include('admin.provider.partials._modal-create')
@endsection

@push('scripts')
    @vite('resources/js/modules/products/product-form.js')
@endpush
