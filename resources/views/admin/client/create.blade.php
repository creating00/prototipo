@extends('adminlte::page')

@section('title', 'Nuevo cliente')

@section('content_header')
    <h1>Nuevo Cliente</h1>
@endsection

@section('content')

    @include('admin.client.partials._form', [
        'mode' => 'create',
        'action' => '/api/clients',
    ])

@endsection
