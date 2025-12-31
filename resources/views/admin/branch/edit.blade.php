@extends('layouts.app')

@section('page-title', 'Editar Sucursal')

@section('content')
    <x-adminlte.form method="PUT" action="{{ route('web.branches.update', $branch->id) }}" title="Editar Sucursal"
        submit-text="Actualizar Sucursal" submitting-text="Actualizando sucursal...">
        @include('admin.branch.partials._form', [
            'branch' => $branch,
            'provinces' => $provinces,
        ])
    </x-adminlte.form>
@endsection
