@extends('adminlte::page')

@section('title', 'Crear categoría')

@section('content_header')
    <h1>Nueva categoría</h1>
@endsection

@section('content')

    <form id="formCreate">

        @include('admin.category.partials._form')

        <button class="btn btn-primary">Guardar</button>
        <a href="{{ route('category.index') }}" class="btn btn-secondary">Volver</a>

    </form>

@endsection

@section('js')
    <script>
        document.querySelector('#formCreate').addEventListener('submit', async e => {
            e.preventDefault();

            await axios.post('/api/categories', {
                name: document.querySelector('#name').value
            });

            window.location.href = "{{ route('category.index') }}";
        });
    </script>
@endsection
