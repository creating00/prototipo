<li class="nav-item dropdown">
    <a class="nav-link" data-bs-toggle="dropdown" href="#">
        <i class="bi bi-bell-fill"></i>
        @if ($unreadCount > 0)
            <span class="navbar-badge badge text-bg-warning">{{ $unreadCount }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <span class="dropdown-item dropdown-header">{{ $unreadCount }} Notificaciones</span>
        <div class="dropdown-divider"></div>

        @forelse($notifications as $notification)
            @php
                $orderId = $notification->data['order_id'] ?? null;
                $orderNumber = \App\Models\Order::formatOrderNumber($orderId);

                // Ahora apuntamos a la ruta de redirección pasando el ID de la notificación (UUID)
                $targetUrl = route('web.notifications.read-and-redirect', $notification->id);
            @endphp

            <a href="{{ $targetUrl }}" class="dropdown-item"
                title="{{ $orderNumber }} - {{ $notification->data['message'] ?? 'Notificación' }}">
                <i class="bi bi-box-seam me-2 text-primary"></i>
                <span class="text-truncate d-inline-block align-middle" style="max-width: 180px;">
                    <span class="fw-bold">{{ $orderNumber }}</span> <br>
                    <small class="text-muted">{{ $notification->data['message'] ?? 'Nueva notificación' }}</small>
                </span>
                <span class="float-end text-secondary fs-7">
                    {{ $notification->created_at->diffForHumans(null, true, true) }}
                </span>
            </a>
            <div class="dropdown-divider"></div>
        @empty
            <span class="dropdown-item text-center text-muted fs-7">
                No hay notificaciones nuevas
            </span>
            <div class="dropdown-divider"></div>
        @endforelse

        <a href="{{ route('web.notifications.index') }}" class="dropdown-item dropdown-footer">
            Ver todas las notificaciones
        </a>
    </div>
</li>
