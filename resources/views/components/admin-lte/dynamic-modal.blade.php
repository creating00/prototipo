<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">

            {{-- HEADER --}}
            <div class="modal-header border-0 px-4 pt-4">
                <h4 class="modal-title fw-semibold d-flex align-items-center gap-2">
                    <i class="fas fa-layer-group text-primary"></i>
                    {{ $title }}
                </h4>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- DIVIDER --}}
            <div class="px-4">
                <hr class="mt-0 mb-3 text-muted opacity-25">
            </div>

            {{-- BODY --}}
            <div class="modal-body px-4 pb-4">
                <form id="{{ $formId }}" class="needs-validation" novalidate>
                    @csrf

                    @if ($formView)
                        @include($formView, $formData)
                    @else
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" class="form-control form-control-lg rounded-3" name="name"
                                required>
                        </div>
                    @endif
                </form>
            </div>

            {{-- FOOTER --}}
            <div class="modal-footer border-0 px-4 pb-4 d-flex justify-content-between">

                <button type="button" class="btn btn-light border rounded-3 px-4 py-2" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1 opacity-75"></i>
                    Cancelar
                </button>

                <button type="button" id="{{ $btnSaveId }}" class="btn btn-primary rounded-3 px-4 py-2 shadow-sm"
                    data-dynamic-modal-submit data-modal-id="{{ $modalId }}" data-form-id="{{ $formId }}"
                    data-route="{{ $route }}"
                    @if ($selectId) data-select-id="{{ $selectId }}" @endif
                    data-field-name="display_name" data-refresh-on-save="{{ $refreshOnSave ? 'true' : 'false' }}"
                    data-refresh-url="{{ $refreshUrl ?? '' }}">

                    <span class="spinner-border spinner-border-sm d-none me-2"></span>
                    <span class="btn-text fw-semibold">Guardar</span>
                </button>
            </div>
        </div>
    </div>
</div>
