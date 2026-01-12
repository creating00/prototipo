{{-- resources/views/admin/sales/partials/sections/_product_search.blade.php --}}
<div class="compact-input-wrapper position-relative" id="product-search-container">
    <label class="compact-input-label">
        Buscador de Productos <kbd class="kbd-shortcut">F1</kbd>
    </label>
    <div class="input-group input-group-sm">
        <input type="text" id="product_search_input" class="form-control compact-input"
            placeholder="Escriba código o nombre..." autocomplete="off">
    </div>

    {{-- Contenedor de resultados --}}
    <ul class="dropdown-menu w-100 shadow" id="search-results-list"
        style="display: none; max-height: 300px; overflow-y: auto; position: absolute; z-index: 1050;">
    </ul>

    {{-- Spinner de carga --}}
    <div id="search-spinner" class="position-absolute" style="right: 10px; top: 32px; display: none; z-index: 1060;">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
    </div>
</div>

{{-- Templates (se cargan una sola vez) --}}
<template id="tpl-search-item">
    <li>
        <a class="dropdown-item d-flex justify-content-between align-items-center py-2" href="#" data-code="">
            <div class="text-truncate" style="max-width: 70%;">
                <strong class="product-name"></strong><br>
                <small class="text-muted product-meta"></small>
            </div>
            <span class="badge bg-success product-price"></span>
        </a>
    </li>
</template>

<template id="tpl-search-empty">
    <li class="p-3 text-center text-muted">
        <i class="fas fa-exclamation-circle mb-2 d-block"></i>
        <span>No se encontraron productos</span>
    </li>
</template>
