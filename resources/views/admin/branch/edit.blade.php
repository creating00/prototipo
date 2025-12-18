@extends('layouts.app')

@section('page-title', 'Editar Sucursal')

@section('content')
    <x-admin-lte.form method="PUT" action="{{ route('web.branches.update', $branch->id) }}" title="Editar Sucursal"
        submit-text="Actualizar Sucursal" submitting-text="Actualizando sucursal...">
        @include('admin.branch.partials._form', [
            'branch' => $branch,
            'provinces' => $provinces,
        ])
    </x-admin-lte.form>
@endsection
