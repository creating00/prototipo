@extends('adminlte::page')

@section('title', 'Editar producto')

@section('content_header')
    <h1>Editar Producto</h1>
@stop

@section('content')
    <form id="formEdit" data-redirect-url="{{ route('product.index') }}" data-product-id="{{ $product->id }}">
        @include('admin.product.partials._form', [
            'product' => $product,
            'mode' => 'edit',
        ])
    </form>
@stop

@section('js')

    <!-- Librerías core -->
    <script src="{{ asset('js/core/ApiClient.js') }}"></script>
    <script src="{{ asset('js/core/FormValidator.js') }}"></script>

    <!-- Servicios -->
    <script src="{{ asset('js/product/services/SelectService.js') }}"></script>
    <script src="{{ asset('js/product/services/ProductService.js') }}"></script>

    <!-- Forms -->
    <script src="{{ asset('js/product/forms/ProductForm.js') }}"></script>

    <!-- Main específico para edit -->
    <script src="{{ asset('js/product/edit/main.js') }}"></script>
@stop
