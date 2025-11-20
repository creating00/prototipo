@extends('adminlte::page')

@section('title', 'Editar Orden')

@section('content_header')
    <h1>Editar Orden #{{ $id }}</h1>
@stop

@section('content')
    @include('admin.order.partials._form', ['order' => $order])
@stop

@section('js')
    <script src="{{ asset('js/order/order-client.js') }}"></script>
    <script src="{{ asset('js/order/order-products.js') }}"></script>
    <script src="{{ asset('js/order/order-form-handler.js') }}"></script>
    <script src="{{ asset('js/order/order-form.js') }}"></script>
    <script src="{{ asset('js/order-form-init.js') }}"></script>

    <script>
        window.authUserId = {{ auth()->id() }};
        window.orderFormUrl = '{{ $order ? '/api/orders/' . $order->id : '/api/orders' }}';
        window.orderFormMethod = '{{ $order ? 'PUT' : 'POST' }}';
        window.orderIndexUrl = "{{ route('order.index') }}";
        window.currentOrderId = {{ $order ? $order->id : 'null' }};
    </script>
    <style>
        .product-row {
            align-items: center;
        }

        .subtotal {
            font-weight: bold;
            color: #28a745;
        }

        #modalClientResults tr {
            cursor: pointer;
        }

        #modalClientResults tr:hover {
            background-color: #f8f9fa;
        }
    </style>
@stop
