@extends('layouts.app')

@section('page-title', 'Crear Categoría')

@section('content')
    <x-admin-lte.form action="{{ route('web.categories.store') }}" title="Crear Categoría" submit-text="Guardar Categoría"
        submitting-text="Creando categoría...">

        @include('admin.category.partials._form', [
            'category' => null,
        ])

    </x-admin-lte.form>
@endsection
