{{-- resources/views/components/admin-lte/app-main.blade.php --}}
<main class="app-main">
    <x-adminlte.content-header :title="$pageTitle" :breadcrumbs="$breadcrumbs" />

    <div class="app-content">
        <div class="container-fluid">
            {{ $slot }}
        </div>
    </div>
</main>
