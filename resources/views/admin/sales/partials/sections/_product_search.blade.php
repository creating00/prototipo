{{-- resources/views/admin/sales/partials/sections/_product_search.blade.php --}}
<div class="compact-input-wrapper position-relative product-search-panel" id="product-search-container">
    <label class="compact-input-label">
        Buscador de Productos <kbd class="kbd-shortcut">F1</kbd>
    </label>

    <div class="input-group input-group-sm position-relative">
        <span class="input-group-text bg-light border-end-0">
            <i class="fas fa-search text-muted"></i>
        </span>
        <input type="text" id="product_search_input" class="form-control compact-input border-start-0 ps-0"
            placeholder="Escriba código o nombre..." autocomplete="off">
        <button type="button" class="btn btn-outline-success border-start-0" id="btn-open-quick-product" data-bs-toggle="modal" data-bs-target="#modalQuickProduct" title="Registrar Producto Nuevo">
            <i class="fas fa-plus me-1"></i> Nuevo
        </button>

        <div id="search-spinner" class="position-absolute"
            style="right: 80px; top: 50%; transform: translateY(-50%); display: none; z-index: 1060;">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
        </div>
    </div>

    {{-- Indicador de filtro activo: Posicionado abajo a la derecha --}}
    <div id="search-filter-indicator" class="d-flex justify-content-end mt-1" style="min-height: 1.2rem;"></div>

    <ul class="dropdown-menu product-search-results shadow" id="search-results-list">
    </ul>
</div>

{{-- Templates (se cargan una sola vez) --}}
<template id="tpl-search-item">
    <li>
        <a class="dropdown-item product-search-result" href="#" data-code="">
            <div class="product-search-main">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="product-search-code product-code"></span>
                    <span class="product-search-category product-category d-none"></span>
                </div>
                <strong class="product-search-name product-name"></strong>
                <small class="product-search-description product-description d-none"></small>
                <div class="product-search-meta">
                    <span class="product-meta"></span>
                    <span class="product-search-status product-status"></span>
                </div>
            </div>
            <div class="product-search-numbers">
                <span class="product-search-stock product-stock"></span>
                <span class="product-search-money product-cost d-none" title="Costo de compra">
                    <i class="fas fa-tag me-1"></i><span class="product-cost-text"></span>
                </span>
                <span class="product-search-money product-price"></span>
            </div>
        </a>
    </li>
</template>

<template id="tpl-search-empty">
    <li class="p-3 text-center text-muted">
        <i class="fas fa-exclamation-circle mb-2 d-block"></i>
        <span>No se encontraron productos</span>
    </li>
</template>
