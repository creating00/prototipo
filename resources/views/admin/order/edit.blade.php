@extends('adminlte::page')

@section('title', 'Editar Pedido')

@section('content_header')
    <h1>Editar Pedido #{{ $id }}</h1>
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
