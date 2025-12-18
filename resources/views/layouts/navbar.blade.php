<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Botón para colapsar sidebar -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ url('/') }}" class="nav-link">Inicio</a>
        </li>
    </ul>

    <!-- Lado derecho del navbar -->
    <ul class="navbar-nav ms-auto">
        <!-- Usuario autenticado -->
        @auth
            <li class="nav-item dropdown">
                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    {{ Auth::user()->name }}
                </a>

                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">Perfil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item" type="submit">Cerrar sesión</button>
                    </form>
                </div>
            </li>
        @endauth
    </ul>
</nav>
