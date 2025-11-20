@extends('adminlte::page')

@section('title', 'Crear sucursal')

@section('content_header')
    <h1>Nueva sucursal</h1>
@endsection

@section('content')

    <form id="formCreate">

        <div class="mb-3">
            <label>Nombre</label>
            <input type="text" id="name" class="form-control">
        </div>

        <div class="mb-3">
            <label>Dirección</label>
            <input type="text" id="address" class="form-control">
        </div>

        <button class="btn btn-primary">Guardar</button>
        <a href="{{ route('branch.index') }}" class="btn btn-secondary">Volver</a>

    </form>

@endsection

@section('js')
    <script>
        document.querySelector('#formCreate').addEventListener('submit', async e => {
            e.preventDefault();

            await axios.post('/api/branches', {
                name: document.querySelector('#name').value,
                address: document.querySelector('#address').value,
            });

            window.location.href = "{{ route('branch.index') }}";
        });
    </script>
@endsection
