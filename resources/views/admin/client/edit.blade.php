@extends('layouts.app')

@section('page-title', 'Editar Cliente')

@section('content')
    <x-adminlte.form action="{{ route('web.clients.update', $client->id) }}" method="PUT"
        title="Editar Cliente: {{ $client->full_name }}" submit-text="Actualizar Cliente"
        submitting-text="Actualizando cliente...">
        @include('admin.client.partials._form', [
            'client' => $client,
        ])
    </x-adminlte.form>
@endsection
