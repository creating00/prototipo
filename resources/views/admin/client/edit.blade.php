@extends('adminlte::page')

@section('title', 'Editar cliente')

@section('content_header')
    <h1>Editar Cliente</h1>
@endsection

@section('content')

    @include('admin.client.partials._form', [
        'mode' => 'edit',
        'action' => '/api/clients/' . $client->id,
        'client' => $client,
    ])

@endsection
