@extends('layouts.app')

@section('page-title', 'Crear Producto')

@section('content')
    <x-adminlte.alert-manager />
    <x-adminlte.form action="{{ route('web.products.store') }}" method="POST" title="Crear Nuevo Producto"
        submit-text="Guardar Producto" submitting-text="Creando producto..." enctype="multipart/form-data"
        {{-- ¡Necesario para subir imágenes! --}}>
        @include('admin.product.partials._form', [
            'formData' => $formData,
        ])
    </x-adminlte.form>

    @include('admin.category.partials._modal-create')
    @include('admin.branch.partials._modal-create')
    @include('admin.product.partials._image-modal-preview')
@endsection

@push('scripts')
    @vite('resources/js/modules/products/create.js')
@endpush
