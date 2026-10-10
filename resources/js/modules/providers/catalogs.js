import TomSelect from 'tom-select';
import Swal from 'sweetalert2';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
import '../../../css/modules/providers/catalogs.css';

const optionsError = document.querySelector('[data-catalog-options-error]');
let clearingCatalogs = false;

// New visits start collapsed; pagination and progress reloads keep the current choice.
const restoreSections = performance.getEntriesByType('navigation')[0]?.type === 'reload'
    || ['#catalog-published-products', '#catalog-history'].includes(window.location.hash);
document.querySelectorAll('[data-catalog-section]').forEach((section) => {
    const storageKey = `catalog-section:${section.dataset.catalogSection}`;
    const label = section.querySelector('[data-catalog-toggle-label]');
    const updateLabel = () => {
        label.textContent = section.open ? label.dataset.openLabel : label.dataset.closedLabel;
    };
    try {
        section.open = restoreSections && sessionStorage.getItem(storageKey) === 'true';
        sessionStorage.setItem(storageKey, String(section.open));
    } catch { /* The native toggle still works when browser storage is unavailable. */ }
    updateLabel();
    section.addEventListener('toggle', () => {
        updateLabel();
        try {
            sessionStorage.setItem(storageKey, String(section.open));
        } catch { /* Keep the control usable without persistence. */ }
    });
});

document.querySelector('[data-catalog-clear-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const result = await Swal.fire({
        title: '¿Limpiar todos los catálogos web?',
        text: 'Se borrarán las vistas previas, los productos guardados y todo el historial de todos los proveedores. Las consultas en curso se cancelarán. Las URLs y el inventario se conservan. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, borrar todo el catálogo web',
        cancelButtonText: 'Cancelar',
        focusCancel: true,
        customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-secondary', actions: 'gap-3' },
        buttonsStyling: false,
    });
    if (!result.isConfirmed) return;
    clearingCatalogs = true;
    form.querySelector('[name="confirm_clear"]').value = '1';
    const button = form.querySelector('button');
    button.disabled = true;
    button.textContent = 'Limpiando catálogos…';
    HTMLFormElement.prototype.submit.call(form);
});

function imagePlaceholder() {
    const placeholder = document.createElement('span');
    placeholder.className = 'catalog-combo-placeholder';
    placeholder.textContent = 'Sin imagen';
    return placeholder;
}

function productOption(data, selected = false) {
    if (!data.value) return dropdownMessage(data.text || 'Seleccionar producto');
    const container = document.createElement('div');
    container.className = `catalog-combo-option${selected ? ' catalog-combo-selected' : ''}`;
    if (data.image) {
        const image = document.createElement('img');
        image.className = 'catalog-combo-image';
        image.src = data.image;
        image.alt = '';
        image.loading = 'lazy';
        image.referrerPolicy = 'no-referrer';
        image.addEventListener('error', () => image.replaceWith(imagePlaceholder()), { once: true });
        container.append(image);
    } else {
        container.append(imagePlaceholder());
    }
    const copy = document.createElement('span');
    copy.className = 'catalog-combo-copy';
    const name = document.createElement('span');
    name.textContent = data.text || 'Nombre no informado';
    const metadata = document.createElement('small');
    metadata.className = 'catalog-combo-meta';
    metadata.textContent = `${data.provider} · ${data.code ?? 'Sin código'} · ${data.price ?? 'Sin precio'} ${data.currency ?? 'Sin moneda'} · ${data.queried_at}`;
    const source = document.createElement('small');
    source.className = 'catalog-combo-meta';
    source.textContent = data.source_url || '';
    copy.append(name, metadata, source);
    container.append(copy);
    return container;
}

function dropdownMessage(text) {
    const message = document.createElement('div');
    message.className = 'small px-3 py-2 text-body-secondary';
    message.textContent = text;
    return message;
}

document.getElementById('catalog-provider')?.addEventListener('change', (event) => {
    event.target.form.requestSubmit();
});

document.getElementById('catalog-source')?.addEventListener('change', (event) => {
    event.target.form.requestSubmit();
});

function showCatalogProgress(selector) {
    const progress = document.querySelector(selector);
    progress?.classList.remove('d-none');
    progress?.setAttribute('aria-busy', 'true');
    document.querySelectorAll('[data-catalog-query-form] button, [data-catalog-process-form] button').forEach((button) => {
        button.disabled = true;
    });
}

document.querySelector('[data-catalog-query-form]')?.addEventListener('submit', (event) => {
    showCatalogProgress('[data-catalog-query-progress]');
    document.querySelector('[data-catalog-progress-label]').textContent = 'Consultando el catálogo del proveedor… Los productos aparecerán a medida que los recibamos.';
    event.target.querySelector('button').textContent = 'Consultando…';
});

document.querySelector('[data-catalog-process-form]')?.addEventListener('submit', (event) => {
    showCatalogProgress('[data-catalog-process-progress]');
    event.target.querySelector('button').textContent = 'Procesando…';
});

function initializeProductSearch(select, providerFilter) {
    return new TomSelect(select, {
        plugins: ['virtual_scroll'],
        maxOptions: null,
        maxItems: 1,
        placeholder: 'Buscar productos procesados',
        allowEmptyOption: false,
        preload: 'focus',
        lockOptgroupOrder: true,
        score: () => () => 1,
        firstUrl(query) {
            const url = new URL(select.dataset.productsUrl, window.location.origin);
            url.searchParams.set('q', query.trim());
            if (providerFilter.value) url.searchParams.set('provider_id', providerFilter.value);
            return url.toString();
        },
        async load(query, callback) {
            const url = this.getUrl(query);
            if (!url) {
                callback([], []);
                return;
            }
            const controller = new AbortController();
            this.catalogRequests ??= new Set();
            this.catalogRequests.add(controller);
            const timeout = setTimeout(() => controller.abort(), 15000);
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('No se pudo cargar el historial');
                const data = await response.json();
                if (select.tomselect !== this || controller.signal.aborted) return;
                this.setNextUrl(query, data.next_page_url);
                this.catalogLoadFailed = false;
                optionsError?.classList.add('d-none');
                callback(data.options, data.optgroups);
            } catch {
                if (select.tomselect !== this) return;
                this.setNextUrl(query, url);
                this.catalogLoadFailed = true;
                optionsError?.classList.remove('d-none');
                callback([], []);
            } finally {
                clearTimeout(timeout);
                this.catalogRequests.delete(controller);
            }
        },
        onInitialize() {
            this.control_input.maxLength = 100;
            Object.values(this.options).forEach((option) => {
                if (option.optgroup && !this.optgroups[option.optgroup]) {
                    this.addOptionGroup(option.optgroup, { value: option.optgroup, label: option.provider });
                }
            });
        },
        onDropdownOpen() {
            if (this.catalogLoadFailed) this.load(this.lastValue);
        },
        render: {
            option: (data) => productOption(data),
            item: (data) => productOption(data, true),
            loading_more: () => dropdownMessage('Desplazate para cargar más productos…'),
            no_more_results: () => dropdownMessage('Fin de los productos procesados para esta búsqueda.'),
            no_results: () => dropdownMessage('No hay productos procesados para esta búsqueda.'),
        },
    });
}

document.querySelectorAll('.catalog-comparison-provider').forEach((providerFilter) => {
    const select = document.getElementById(providerFilter.dataset.productSelect);
    initializeProductSearch(select, providerFilter);
    providerFilter.addEventListener('change', () => {
        select.tomselect.catalogRequests?.forEach((controller) => controller.abort());
        select.tomselect.destroy();
        select.replaceChildren(new Option('Seleccionar producto', ''));
        optionsError?.classList.add('d-none');
        initializeProductSearch(select, providerFilter).focus();
    });
});

// Exclude optional empty slots; server validates every submitted reference again.
document.querySelector('[action$="provider-catalogs/compare"]')?.addEventListener('submit', () => {
    document.querySelectorAll('.catalog-comparison-select').forEach((select) => {
        select.disabled = !select.value;
    });
});

// Refreshes only the local result screen, never initiates a provider query.
if (document.querySelector('[data-catalog-page]')?.dataset.pending === 'true') {
    const refreshTimer = setInterval(() => {
        if (!clearingCatalogs && !Swal.isVisible() && ![...document.querySelectorAll('.catalog-comparison-select')].some((select) => select.value || select.tomselect?.isFocused)) {
            clearInterval(refreshTimer);
            window.location.reload();
        }
    }, 10000);
}
