@extends('layouts.app')

@section('page-title', 'Editar Categoría')

@section('content')
    <x-adminlte.form action="{{ route('web.categories.update', $category->id) }}" title="Editar Categoría"
        submit-text="Actualizar Categoría" submitting-text="Actualizando categoría..." method="PUT">

        @include('admin.category.partials._form', [
            'category' => $category,
        ])

    </x-adminlte.form>
@endsection
