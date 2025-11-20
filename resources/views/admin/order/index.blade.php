@extends('adminlte::page')

@section('title', 'Pedidos')

@section('content_header')
    <h1>Pedidos</h1>
@stop

@section('content')
    <a href="{{ route('order.create') }}" class="btn btn-primary mb-3">Nuevo Pedido</a>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped" id="tableOrders">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Cantidad Total</th>
                        <th>Monto Total</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
    <script>
        async function loadOrders() {
            try {
                const res = await axios.get('/api/orders');

                const rows = res.data.map(order => {
                    // Calcular cantidad total de productos
                    const totalQuantity = order.items.reduce((sum, item) => sum + item.quantity, 0);

                    // Lista de productos (máximo 3 para no hacer muy larga la celda)
                    const productsList = order.items.slice(0, 3).map(item =>
                        `${item.product?.name || 'Producto'} (${item.quantity})`
                    ).join('<br>');

                    // Si hay más de 3 productos, mostrar "..."
                    const moreProducts = order.items.length > 3 ?
                        `<br><small class="text-muted">+${order.items.length - 3} más</small>` : '';

                    return `
                <tr>
                    <td>${order.id}</td>
                    <td>
                        <strong>${order.client?.full_name || 'N/A'}</strong><br>
                        <small class="text-muted">Doc: ${order.client?.document || ''}</small>
                    </td>
                    <td>
                        ${productsList}${moreProducts}
                    </td>
                    <td class="text-center">${totalQuantity}</td>
                    <td class="text-right">S/. ${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td>${order.user?.name || 'Sistema'}</td>
                    <td>${new Date(order.created_at).toLocaleDateString()}</td>
                    <td>
                        <a href="order/${order.id}/edit" class="btn btn-sm btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="deleteOrder(${order.id})" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                `;
                });

                document.querySelector('#tableOrders tbody').innerHTML = rows.join('');

            } catch (error) {
                console.error('Error loading orders:', error);
                alert('Error al cargar los pedidos');
            }
        }

        async function deleteOrder(id) {
            if (!confirm("¿Estás seguro de eliminar este pedido?")) return;

            try {
                await axios.delete(`/api/orders/${id}`);
                loadOrders();
            } catch (error) {
                console.error('Error deleting order:', error);
                alert('Error al eliminar el pedido');
            }
        }

        // Cargar órdenes al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadOrders();
        });
    </script>

    <style>
        #tableOrders td {
            vertical-align: middle;
        }
    </style>
@stop
