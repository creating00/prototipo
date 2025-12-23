<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $large ? 'modal-lg' : '' }} modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">

            {{-- HEADER --}}
            <div class="modal-header border-0 px-4 pt-4">
                <h4 class="modal-title fw-semibold d-flex align-items-center gap-2">
                    <i class="fas fa-layer-group text-primary"></i>
                    {{ $title }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            {{-- DIVIDER --}}
            <div class="px-4">
                <hr class="mt-0 mb-3 text-muted opacity-25">
            </div>

            {{-- BODY --}}
            <div class="modal-body px-4 pb-4">
                <form id="{{ $formId }}" class="needs-validation" novalidate method="POST"
                    action="{{ $submitUrl }}">
                    @csrf
                    @method('PUT') 
                    <div id="{{ $formId }}Content">
                        <p class="text-center text-muted">Cargando...</p>
                    </div>
                </form>
            </div>

            {{-- FOOTER --}}
            <div class="modal-footer border-0 px-4 pb-4 d-flex justify-content-between">
                <button type="button" class="btn btn-light border rounded-3 px-4 py-2" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1 opacity-75"></i> Cancelar
                </button>

                <button type="submit" form="{{ $formId }}" class="btn btn-primary rounded-3 px-4 py-2 shadow-sm"
                    id="{{ $btnSaveId }}">
                    <span class="spinner-border spinner-border-sm d-none me-2"></span>
                    <span class="btn-text fw-semibold">Guardar</span>
                </button>
            </div>

        </div>
    </div>
</div>
