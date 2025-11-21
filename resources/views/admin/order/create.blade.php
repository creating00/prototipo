@extends('adminlte::page')

@section('title', 'Nuevo Pedido')

@section('content_header')
    <h1>Nuevo Pedido</h1>
@stop

@section('content')
    @include('admin.order.partials._form', ['order' => $order])
    @include('admin.payment.partials._payment')
@stop

@section('js')
    @include('admin.order.partials._scripts')

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
