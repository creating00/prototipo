<div class="form-section-container mb-4">
    <h3 class="form-section-title">Información del Proveedor</h3>

    <div class="row">
        <div class="col-md-6">
            <div class="compact-input-wrapper position-relative" id="provider-search-container">
                <label class="compact-input-label">Proveedor</label>

                <div class="input-group input-group-sm">
                    {{-- Input de búsqueda --}}
                    <input type="text" id="provider_search_input" class="form-control compact-input"
                        placeholder="Buscar por CUIT, Razón Social o Nombre Corto..." autocomplete="off">

                    {{-- Contador al lado del input --}}
                    <span class="input-group-text bg-light border-end-0 text-muted" id="provider-count-display"
                        title="Total de proveedores">
                        <i class="fas fa-users me-1"></i> <span id="provider-main-count">0</span>
                    </span>

                    {{-- Botón para abrir modal de lista --}}
                    <button class="btn btn-outline-secondary" type="button" id="btn-list-providers"
                        data-bs-toggle="modal" data-bs-target="#modalProviderList" title="Listado de proveedores">
                        <i class="fas fa-list"></i>
                    </button>

                    {{-- Botón para añadir nuevo --}}
                    <button class="btn btn-primary" type="button" id="btn-quick-add-provider" title="Nuevo proveedor">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                {{-- Hidden input para el ID --}}
                <input type="hidden" name="provider_id" id="selected_provider_id"
                    value="{{ old('provider_id', $formData->product?->provider_id) }}">

                {{-- Lista de resultados rápidos --}}
                <ul class="dropdown-menu w-100 shadow" id="provider-results-list"
                    style="display: none; max-height: 250px; overflow-y: auto; position: absolute; z-index: 1050;">
                </ul>

                {{-- Spinner --}}
                <div id="provider-search-spinner" class="position-absolute"
                    style="right: 120px; top: 32px; display: none; z-index: 1060;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Lista de Proveedores --}}
<div class="modal fade" id="modalProviderList" tabindex="-1" aria-labelledby="modalProviderListLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="modalProviderListLabel">
                    <i class="fas fa-address-book me-2"></i>Seleccionar Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover w-100" id="table-modal-providers">
                        <thead class="table-dark">
                            <tr>
                                <th>Razón Social</th>
                                <th>CUIT</th>
                                <th>Teléfono</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="provider-table-body">
                            {{-- Contenido dinámico --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Templates --}}
<template id="tpl-provider-item">
    <li>
        <a class="dropdown-item d-flex justify-content-between align-items-center py-2" href="#" data-id="">
            <div class="text-truncate" style="max-width: 85%;">
                <strong class="provider-biz-name"></strong><br>
                <small class="text-muted provider-tax-id"></small>
            </div>
        </a>
    </li>
</template>

{{-- Template para filas de la tabla de proveedores seleccionados --}}
<template id="tpl-selected-provider-row">
    <tr>
        <td class="col-name"></td>
        <td class="col-tax-id"></td>
        <td class="col-phone"></td>
        <td class="text-end">
            <input type="hidden" name="providers[]" value="">
            <button type="button" class="btn btn-sm btn-outline-danger remove-provider">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<template id="tpl-search-empty">
    <li class="p-3 text-center text-muted">
        <i class="fas fa-exclamation-circle mb-2 d-block"></i>
        <span>No se encontraron proveedores</span>
    </li>
</template>
