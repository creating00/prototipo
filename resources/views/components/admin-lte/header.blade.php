<!-- resources/views/components/adminlte/header.blade.php -->

<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block"><a href="#" class="nav-link">Home</a></li>
        </ul>
        <!--end::Start Navbar Links-->

        <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto">
            <!-- Navbar Search -->
            {{-- <x-adminlte.navbar-search /> --}}

            <!-- Messages Dropdown Menu -->
            {{-- <x-adminlte.dropdown-menu /> --}}

            <!-- Notifications Dropdown -->
            {{-- <x-adminlte.notifications-dropdown /> --}}

            <!-- Fullscreen -->
            <li class="nav-item">
                <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display:none"></i>
                </a>
            </li>

            <!-- User Menu -->
            <x-adminlte.user-menu />
        </ul>
        <!--end::End Navbar Links-->
    </div>
</nav>
