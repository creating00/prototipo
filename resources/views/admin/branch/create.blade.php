@extends('layouts.app')

@section('page-title', 'Crear Sucursal')

@section('content')
    <x-admin-lte.form action="{{ route('web.branches.store') }}" title="Crear Sucursal" submit-text="Guardar Sucursal"
        submitting-text="Creando sucursal...">
        @include('admin.branch.partials._form', [
            'branch' => null,
            'provinces' => $provinces,
        ])
    </x-admin-lte.form>
@endsection
