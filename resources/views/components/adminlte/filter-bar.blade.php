@props([
    'action' => null,
])

<form @if ($action) action="{{ $action }}" @endif method="GET" class="mb-4">
    <div class="row g-3 align-items-end">
        {{ $slot }}

        <div class="col-auto">
            <button type="submit" class="btn btn-primary">
                Filtrar
            </button>
        </div>
    </div>
</form>
