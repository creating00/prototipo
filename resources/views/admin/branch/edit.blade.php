@extends('adminlte::page')

@section('title', 'Editar sucursal')

@section('content_header')
    <h1>Editar sucursal</h1>
@endsection

@section('content')

    <form id="formEdit">

        <div class="mb-3">
            <label>Nombre</label>
            <input type="text" id="name" class="form-control">
        </div>

        <div class="mb-3">
            <label>Dirección</label>
            <input type="text" id="address" class="form-control">
        </div>

        <button class="btn btn-primary">Actualizar</button>
        <a href="{{ route('branch.index') }}" class="btn btn-secondary">Volver</a>

    </form>

@endsection

@section('js')
    <script>
        const ID = {{ $id }};

        document.addEventListener("DOMContentLoaded", async () => {
            const response = await axios.get(`/api/branches/${ID}`);
            const branch = response.data;

            document.querySelector('#name').value = branch.name;
            document.querySelector('#address').value = branch.address;
        });

        document.querySelector('#formEdit').addEventListener('submit', async e => {
            e.preventDefault();

            await axios.put(`/api/branches/${ID}`, {
                name: document.querySelector('#name').value,
                address: document.querySelector('#address').value,
            });

            window.location.href = "{{ route('branch.index') }}";
        });
    </script>
@endsection
