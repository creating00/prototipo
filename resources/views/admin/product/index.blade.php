@extends('adminlte::page')

@section('title', 'Productos')

@section('content_header')
    <h1>Productos</h1>
@endsection

@section('content')

    <a href="{{ route('product.create') }}" class="btn btn-primary mb-3">Nuevo Producto</a>

    <table class="table table-bordered" id="tableProducts">
        <thead>
            <tr>
                <th>Imagen</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Stock</th>
                <th>Sucursal</th>
                <th>Precio Venta</th>
                <th>Rating</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

@endsection

@section('js')
    <script>
        const currencyFormatter = new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 2
        });

        async function loadProducts() {
            try {
                const res = await axios.get('/api/products');
                const products = Array.isArray(res.data) ? res.data : [];
                const rows = products.map(p => {
                    const imageUrl = p.image ||
                    '/images/placeholder.png'; // ajusta la ruta del placeholder si hace falta
                    const avg = (p.average_rating !== null && p.average_rating !== undefined) ?
                        Number(p.average_rating).toFixed(1) :
                        '—';
                    const salePrice = (p.sale_price !== null && p.sale_price !== undefined) ?
                        currencyFormatter.format(Number(p.sale_price)) :
                        '—';

                    return `
                        <tr>
                            <td style="width:64px">
                                <img src="${imageUrl}" alt="${p.name}" style="width:48px;height:48px;object-fit:cover;border-radius:4px">
                            </td>
                            <td>${escapeHtml(p.code)}</td>
                            <td>${escapeHtml(p.name)}</td>
                            <td>${escapeHtml(p.category?.name ?? '')}</td>
                            <td>${Number(p.stock ?? 0)}</td>
                            <td>${escapeHtml(p.branch?.name ?? '')}</td>
                            <td>${salePrice}</td>
                            <td>${avg}</td>
                            <td>
                                <a href="/admin/product/${p.id}/edit" class="btn btn-sm btn-warning">Editar</a>
                                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.id})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                });

                document.querySelector('#tableProducts tbody').innerHTML = rows.join('') ||
                    '<tr><td colspan="9" class="text-center">No hay productos</td></tr>';
            } catch (err) {
                console.error(err);
                alert('Error cargando productos. Revisa la consola para más detalles.');
            }
        }

        async function deleteProduct(id) {
            if (!confirm("¿Eliminar producto?")) return;

            try {
                await axios.delete(`/api/products/${id}`);
                await loadProducts();
            } catch (err) {
                console.error(err);
                const message = err.response?.data?.error || 'Error eliminando el producto';
                alert(message);
            }
        }

        // pequeño helper para evitar inyección de HTML
        function escapeHtml(unsafe) {
            if (unsafe === null || unsafe === undefined) return '';
            return String(unsafe)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        loadProducts();
    </script>
@endsection
