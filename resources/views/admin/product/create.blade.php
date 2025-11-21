@extends('adminlte::page')

@section('title', 'Nuevo producto')

@section('content_header')
    <h1>Nuevo Producto</h1>
@stop

@section('content')
    <form id="formCreate" data-redirect-url="{{ route('product.index') }}">
        @include('admin.product.partials._form', [
            'product' => null,
            'mode' => 'create',
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

    <!-- Main específico para create -->
    <script src="{{ asset('js/product/create/main.js') }}"></script>
@stop
