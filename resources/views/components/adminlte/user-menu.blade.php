{{-- resources/views/components/admin-lte/user-menu.blade.php --}}
<li class="nav-item dropdown user-menu">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <img src="{{ $userImage }}" class="user-image rounded-circle shadow" alt="User Image" />
        <span class="d-none d-md-inline">
            {{ $user ? $user->name : 'Usuario' }}
        </span>
    </a>
    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
        <li class="user-header text-bg-primary">
            <img src="{{ $userImage }}" class="rounded-circle shadow" alt="User Image" />
            <p>
                {{ $user ? $user->name : 'Usuario' }} - {{ $userRole }}
                @if ($user && $user->created_at)
                    <small>Miembro desde {{ $user->created_at->format('M. Y') }}</small>
                @else
                    <small>Miembro desde {{ now()->format('M. Y') }}</small>
                @endif
            </p>
        </li>
        <li class="user-footer d-flex justify-content-between align-items-center px-3 py-2">
            {{-- Botón que abre el modal --}}
            <button type="button" class="btn btn-default btn-flat" data-bs-toggle="modal"
                data-bs-target="#modalChangePassword">
                <i class="fas fa-key me-1"></i> Contraseña
            </button>

            {{-- Formulario de Cierre de Sesión --}}
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-default btn-flat">
                    Cerrar Sesión <i class="fas fa-sign-out-alt ms-1"></i>
                </button>
            </form>
        </li>
    </ul>
</li>
