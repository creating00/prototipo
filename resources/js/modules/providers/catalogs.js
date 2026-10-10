import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
import '../../../css/modules/providers/catalogs.css';

const optionsError = document.querySelector('[data-catalog-options-error]');

function imagePlaceholder() {
    const placeholder = document.createElement('span');
    placeholder.className = 'catalog-combo-placeholder';
    placeholder.textContent = 'Sin imagen';
    return placeholder;
}

function productOption(data, selected = false) {
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
    copy.append(name, metadata);
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
    document.querySelector('[data-catalog-progress-label]').textContent = 'Iniciando consulta… Leyendo los productos publicados en la URL del proveedor.';
    event.target.querySelector('button').textContent = 'Consultando…';
});

document.querySelector('[data-catalog-process-form]')?.addEventListener('submit', (event) => {
    showCatalogProgress('[data-catalog-process-progress]');
    event.target.querySelector('button').textContent = 'Procesando…';
});

document.querySelectorAll('.catalog-comparison-select').forEach((select) => {
    new TomSelect(select, {
        plugins: ['virtual_scroll'],
        maxOptions: null,
        maxItems: 1,
        placeholder: 'Buscar productos procesados',
        allowEmptyOption: true,
        preload: 'focus',
        lockOptgroupOrder: true,
        score: () => () => 1,
        firstUrl(query) {
            const url = new URL(select.dataset.productsUrl, window.location.origin);
            url.searchParams.set('q', query.trim());
            return url.toString();
        },
        async load(query, callback) {
            const url = this.getUrl(query);
            if (!url) {
                callback([], []);
                return;
            }
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 15000);
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                if (!response.ok) throw new Error('No se pudo cargar el historial');
                const data = await response.json();
                this.setNextUrl(query, data.next_page_url);
                this.catalogLoadFailed = false;
                optionsError?.classList.add('d-none');
                callback(data.options, data.optgroups);
            } catch {
                this.setNextUrl(query, url);
                this.catalogLoadFailed = true;
                optionsError?.classList.remove('d-none');
                callback([], []);
            } finally {
                clearTimeout(timeout);
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
        if (![...document.querySelectorAll('.catalog-comparison-select')].some((select) => select.value || select.tomselect?.isFocused)) {
            clearInterval(refreshTimer);
            window.location.reload();
        }
    }, 10000);
}
