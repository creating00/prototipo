@php
    /**
     * Lógica de coexistencia y visibilidad:
     * 1. Si showFooter es false, se oculta.
     * 2. Si showFooter es true, se muestra.
     * 3. Si es automático: se muestra si hay algún slot de footer definido
     * O si NO hay acciones en el header (para mostrar el botón por defecto).
     */
    $renderFooter = $showFooter ?? isset($footer) || isset($footerActions) || !isset($headerActions);
@endphp

<div {{ $attributes->merge(['class' => 'card card-primary card-outline mb-4']) }} x-data="{ submitting: false }">

    @if ($title || isset($headerActions))
        <div class="card-header">
            <h3 class="card-title">{{ $title }}</h3>
            <div class="card-tools">
                {{ $headerActions ?? '' }}
            </div>
        </div>
    @endif

    <form id="{{ $formId }}" action="{{ $action }}"
        method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}"
        @if ($enctype) enctype="{{ $enctype }}" @endif  novalidate x-on:submit="submitting = true"
        x-bind:class="{ 'opacity-50 pointer-events-none': submitting }">

        @if (strtoupper($method) !== 'GET')
            @csrf
            @method($method)
        @endif

        <div class="card-body">
            {{ $slot }}
        </div>

        @if ($renderFooter)
            <div class="card-footer">
                @if (isset($footer))
                    {{-- Slot genérico (Prioridad Máxima) --}}
                    {{ $footer }}
                @elseif (isset($footerActions))
                    {{-- Slot de acciones (Prioridad Media) --}}
                    {{ $footerActions }}
                @else
                    {{-- Botón por defecto (Solo si no hay nada en el header) --}}
                    <button type="submit" form="{{ $formId }}" class="btn btn-primary"
                        x-bind:disabled="submitting">
                        <i class="fas fa-save mr-1"></i>
                        <span x-show="!submitting">{{ $submitText }}</span>
                        <span x-show="submitting" x-cloak>
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            {{ $submittingText }}
                        </span>
                    </button>
                @endif
            </div>
        @endif
    </form>
</div>
