import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.css';

document.querySelectorAll('.catalog-comparison-select').forEach((select) => {
    new TomSelect(select, { maxOptions: 100, placeholder: 'Buscar en las últimas consultas', allowEmptyOption: true });
});

// Exclude optional empty slots; server validates every submitted reference again.
document.querySelector('[action$="provider-catalogs/compare"]')?.addEventListener('submit', () => {
    document.querySelectorAll('.catalog-comparison-select').forEach((select) => {
        select.disabled = !select.value;
    });
});

// Refreshes only the local result screen, never initiates a provider query.
if (document.querySelector('[data-catalog-page]')?.dataset.pending === 'true') {
    setTimeout(() => {
        if (![...document.querySelectorAll('.catalog-comparison-select')].some((select) => select.value)) {
            window.location.reload();
        }
    }, 10000);
}
