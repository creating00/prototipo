@extends('layouts.app')

@section('page-title', 'Editar Proveedor')

@section('content')
    <x-adminlte.form action="{{ route('web.providers.update', $provider->id) }}" method="PUT" title="Editar Proveedor"
        submit-text="Actualizar Proveedor" submitting-text="Actualizando proveedor...">
        @include('admin.provider.partials._form', [
            'provider' => $provider,
        ])
    </x-adminlte.form>
@endsection
