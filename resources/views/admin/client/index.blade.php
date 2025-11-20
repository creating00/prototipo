@extends('adminlte::page')

@section('title', 'Clientes')

@section('content_header')
    <h1>Clientes</h1>
@endsection

@section('content')

    <a href="{{ route('client.create') }}" class="btn btn-primary mb-3">Nuevo Cliente</a>

    <table class="table table-bordered" id="tableClients">
        <thead>
            <tr>
                <th>Documento</th>
                <th>Nombre Completo</th>
                <th>Teléfono</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

@endsection

@section('js')
    <script>
        async function loadClients() {
            const res = await axios.get('/api/clients');

            const rows = res.data.map(c => `
                <tr>
                    <td>${c.document}</td>
                    <td>${c.full_name}</td>
                    <td>${c.phone ?? ''}</td>
                    <td>${c.address ?? ''}</td>
                    <td>
                        <a href="/admin/client/${c.id}/edit" class="btn btn-sm btn-warning">Editar</a>
                        <button class="btn btn-sm btn-danger" onclick="deleteClient(${c.id})">Eliminar</button>
                    </td>
                </tr>
            `);

            document.querySelector('#tableClients tbody').innerHTML = rows.join('');
        }

        async function deleteClient(id) {
            if (!confirm("¿Eliminar cliente?")) return;
            await axios.delete(`/api/clients/${id}`);
            loadClients();
        }

        loadClients();
    </script>
@endsection
