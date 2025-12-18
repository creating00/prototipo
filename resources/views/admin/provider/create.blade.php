@extends('layouts.app')

@section('page-title', 'Crear Proveedor')

@section('content')
    <x-admin-lte.form action="{{ route('web.providers.store') }}" title="Crear Proveedor" submit-text="Guardar Proveedor"
        submitting-text="Creando proveedor...">
        @include('admin.provider.partials._form', [
            'provider' => null,
        ])
    </x-admin-lte.form>
@endsection
