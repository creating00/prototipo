@props([
    'title' => 'Centro de Importación',
    'description' => 'Descarga las plantillas base para asegurar el formato correcto de tus datos.',
    'icon' => 'fas fa-file-excel',
])

<div {{ $attributes->merge(['class' => 'row mb-3']) }}>
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-body p-2">
                <div class="d-flex align-items-center">
                    <i class="{{ $icon }} text-info me-3 ms-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <span class="fw-bold d-block">{{ $title }}</span>
                        <span class="text-muted small">{{ $description }}</span>
                    </div>
                    <div class="ms-auto d-flex gap-2 pe-2">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
