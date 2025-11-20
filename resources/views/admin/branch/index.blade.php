@extends('adminlte::page')

@section('title', 'Sucursales')

@section('content_header')
    <h1>Sucursales</h1>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('branch.create') }}" class="btn btn-primary">
            Nueva sucursal
        </a>
    </div>

    <table class="table table-bordered" id="tableBranches">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Opciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

@endsection

@section('js')
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const response = await axios.get('/api/branches');
            const branches = response.data;

            const tbody = document.querySelector('#tableBranches tbody');
            tbody.innerHTML = '';

            branches.forEach(branch => {
                tbody.innerHTML += `
            <tr>
                <td>${branch.id}</td>
                <td>${branch.name}</td>
                <td>${branch.address ?? ''}</td>
                <td>
                    <a href="/admin/branch/${branch.id}/edit" class="btn btn-sm btn-warning">Editar</a>
                    <button onclick="destroyBranch(${branch.id})" class="btn btn-sm btn-danger">Eliminar</button>
                </td>
            </tr>
        `;
            });
        });

        async function destroyBranch(id) {
            if (!confirm("¿Eliminar sucursal?")) return;

            await axios.delete(`/api/branches/${id}`);
            location.reload();
        }
    </script>
@endsection
