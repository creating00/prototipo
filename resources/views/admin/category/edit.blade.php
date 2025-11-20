@extends('adminlte::page')

@section('title', 'Editar categoría')

@section('content_header')
    <h1>Editar categoría</h1>
@endsection

@section('content')

    <form id="formEdit">

        @include('admin.category.partials._form', ['category' => $category])

        <button class="btn btn-primary">Actualizar</button>
        <a href="{{ route('category.index') }}" class="btn btn-secondary">Volver</a>

    </form>

@endsection

@section('js')
    <script>
        document.querySelector('#formEdit').addEventListener('submit', async e => {
            e.preventDefault();

            await axios.put(`/api/categories/{{ $category->id }}`, {
                name: document.querySelector('#name').value
            });

            window.location.href = "{{ route('category.index') }}";
        });
    </script>
@endsection
