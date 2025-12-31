@props([
    'icon' => 'fas fa-chart-bar',
    'title' => 'Sin datos disponibles',
    'description' => 'No hay información para los filtros seleccionados.',
])

<div class="text-center py-5 text-muted">
    <i class="{{ $icon }} fa-3x mb-3"></i>

    <h5 class="fw-semibold">{{ $title }}</h5>

    <p class="mb-0">
        {{ $description }}
    </p>
</div>
