@props([
    'title' => 'Centro de Importación',
    'description' => 'Descarga las plantillas base para asegurar el formato correcto de tus datos.',
    'icon' => 'fas fa-database',
])
<div {{ $attributes->merge(['class' => 'row mb-3']) }}>
    <div class="col-12">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-body p-2">
                <div class="d-flex flex-column flex-md-row align-items-center gap-3">

                    {{-- Texto: solo ocupa lo que necesita --}}
                    <div class="d-flex align-items-center flex-shrink-0">
                        <i class="{{ $icon }} text-info me-3 ms-2" style="font-size: 1.5rem;"></i>
                        <div>
                            <span class="fw-bold d-block">{{ $title }}</span>
                            <span class="text-muted small d-block">{{ $description }}</span>
                        </div>
                    </div>

                    {{-- Divisor vertical entre texto y botones (solo desktop) --}}
                    <div class="vr opacity-25 d-none d-md-block align-self-stretch"></div>

                    {{-- Botones: ocupan todo el espacio restante --}}
                    <div
                        class="flex-grow-1 d-flex align-items-center gap-3 flex-wrap justify-content-center justify-content-md-start pe-md-1 w-100">

                        {{-- Sección: Plantillas --}}
                        @if (isset($templates) && $templates->isNotEmpty())
                            <div class="d-flex flex-column align-items-center gap-1">
                                <span class="text-muted text-uppercase fw-bold"
                                    style="font-size: 0.6rem; letter-spacing: 0.05em;">
                                    <i class="fas fa-file-alt me-1"></i>Plantillas
                                </span>
                                <div class="d-flex gap-1 flex-wrap justify-content-center">
                                    {{ $templates }}
                                </div>
                            </div>
                        @endif

                        @if (isset($templates) &&
                                $templates->isNotEmpty() &&
                                ((isset($exports) && $exports->isNotEmpty()) || (isset($imports) && $imports->isNotEmpty())))
                            <div class="vr opacity-25 d-none d-lg-block" style="height: 30px;"></div>
                        @endif

                        {{-- Sección: Exportar --}}
                        @if (isset($exports) && $exports->isNotEmpty())
                            <div class="d-flex flex-column align-items-center gap-1">
                                <span class="text-muted text-uppercase fw-bold"
                                    style="font-size: 0.6rem; letter-spacing: 0.05em;">
                                    <i class="fas fa-file-export me-1"></i>Exportar
                                </span>
                                <div class="d-flex gap-1 flex-wrap justify-content-center">
                                    {{ $exports }}
                                </div>
                            </div>
                        @endif

                        @if (isset($exports) && $exports->isNotEmpty() && isset($imports) && $imports->isNotEmpty())
                            <div class="vr opacity-25 d-none d-lg-block" style="height: 30px;"></div>
                        @endif

                        {{-- Sección: Importar --}}
                        @if (isset($imports) && $imports->isNotEmpty())
                            <div class="d-flex flex-column align-items-center gap-1">
                                <span class="text-muted text-uppercase fw-bold"
                                    style="font-size: 0.6rem; letter-spacing: 0.05em;">
                                    <i class="fas fa-file-import me-1"></i>Importar
                                </span>
                                <div class="d-flex gap-1 flex-wrap justify-content-center">
                                    {{ $imports }}
                                </div>
                            </div>
                        @endif

                        {{-- Slot genérico (compatibilidad hacia atrás) --}}
                        @if ($slot->isNotEmpty())
                            <div class="d-flex gap-1 flex-wrap">
                                {{ $slot }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
