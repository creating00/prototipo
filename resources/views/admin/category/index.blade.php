@extends('adminlte::page')

@section('title', 'Categorías')

@section('content_header')
    <h1>Categorías</h1>
@endsection

@section('content')

    <div class="mb-3">
        <a href="{{ route('category.create') }}" class="btn btn-primary">
            Nueva categoría
        </a>
    </div>

    <table class="table table-bordered" id="tableCategories">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Opciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

@endsection

@section('js')
    <script>
        document.addEventListener("DOMContentLoaded", async () => {
            const response = await axios.get('/api/categories');
            const categories = response.data;

            const tbody = document.querySelector('#tableCategories tbody');
            tbody.innerHTML = '';

            categories.forEach(category => {
                tbody.innerHTML += `
                    <tr>
                        <td>${category.id}</td>
                        <td>${category.name}</td>
                        <td>
                            <a href="/admin/category/${category.id}/edit" class="btn btn-sm btn-warning">Editar</a>
                            <button onclick="destroyCategory(${category.id})" class="btn btn-sm btn-danger">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        });

        async function destroyCategory(id) {
            if (!confirm("¿Eliminar categoría?")) return;

            await axios.delete(`/api/categories/${id}`);
            location.reload();
        }
    </script>
@endsection
