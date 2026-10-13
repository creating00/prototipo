const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

// Exercise the actual module's async orchestration with isolated control doubles.
function screen(fetch) {
    const status = { textContent: '' };
    const confirmation = { checked: true };
    const wrapper = { setAttribute() {}, removeAttribute() {} };
    const general = { dataset: { productsUrl: '/products', suggestionsUrl: '/suggestions' }, closest: () => wrapper };
    const selects = Array.from({ length: 8 }, () => ({
        value: '', dataset: { productsUrl: '/products' }, replaceChildren() {},
    }));
    const filters = selects.map((select, index) => ({
        value: '', options: [], add(option) { this.options.push(option); },
        dataset: { productSelect: `compare-entry-${index}` }, addEventListener(type, callback) { this[type] = callback; },
        parentElement: { querySelector: () => ({ textContent: '' }) },
    }));
    class Combo {
        constructor(select, settings) {
            this.select = select;
            this.settings = settings;
            this.options = {};
            this.value = '';
            this.addOptions(settings.options || []);
            select.tomselect = this;
        }
        addOptionGroup() {}
        addOptions(options) { options.forEach((option) => { this.options[option.value] = option; }); }
        setValue(value) { this.value = value; this.select.value = value; this.settings.onChange?.call(this, value); }
        getValue() { return this.value; }
        setTextboxValue(query) { this.query = query; }
        refreshOptions() {}
        destroy() { delete this.select.tomselect; }
        focus() {}
    }
    const document = {
        querySelector: (query) => query === '[data-catalog-general-status]' ? status : null,
        querySelectorAll: (query) => query === '.catalog-comparison-provider' ? filters : [],
        getElementById: (id) => id === 'catalog-general-search' ? general : id === 'equivalence-reviewed' ? confirmation : selects[Number(id.replace('compare-entry-', ''))],
    };
    const source = readFileSync(path.join(__dirname, '../../resources/js/modules/providers/catalogs.js'), 'utf8').replace(/^import .*;\r?\n/gm, '');
    vm.runInNewContext(source, { document, TomSelect: Combo, Swal: {}, performance: { getEntriesByType: () => [] }, window: { location: { hash: '', origin: 'http://localhost' } }, URL, AbortController, fetch, setTimeout, clearTimeout, Option: class { constructor(text, value) { this.text = text; this.value = value; } }, sessionStorage: {} });
    const choose = (value) => {
        general.tomselect.value = value;
        general.tomselect.settings.onType.call(general.tomselect, 'Lenovo T14');
        return general.tomselect.settings.onChange.call(general.tomselect, value);
    };
    return { choose, selects, filters, status, confirmation };
}

const group = (provider, ids, selected) => ({ provider_id: provider, provider, options: ids.map((value) => ({ value, optgroup: provider })), selected, has_more: false });
const response = (groups) => ({ ok: true, json: async () => ({ groups, search: 'Lenovo T14', providers_truncated: false }) });

test('general selection assigns providers and candidates, leaves ambiguity open and resets equivalence confirmation', async () => {
    const ui = screen(async () => response([group('1', ['10'], '10'), group('2', ['20', '21'], null)]));
    await ui.choose('10');
    assert.equal(ui.filters[0].value, '1');
    assert.equal(ui.filters[0].options[0].value, '1');
    assert.equal(ui.selects[0].tomselect.getValue(), '10');
    assert.equal(ui.filters[1].value, '2');
    assert.equal(ui.selects[1].tomselect.getValue(), '');
    assert.deepEqual(Object.keys(ui.selects[1].tomselect.options), ['20', '21']);
    assert.equal(ui.selects[1].tomselect.settings.preload, false);
    assert.equal(ui.selects[1].tomselect.query, undefined);
    assert.equal(ui.selects[2].tomselect.getValue(), '');
    assert.equal(ui.confirmation.checked, false);
    assert.match(ui.status.textContent, /2 proveedores/);
});

test('ambiguous products start as constructor options and filter locally by name or code', async () => {
    const candidate = group('2', ['20', '21'], null);
    candidate.options[0].text = 'Módulo Samsung A02 con marco';
    candidate.options[0].code = 'MOD-A02-CM';
    candidate.options[1].text = 'Módulo Samsung A02 sin marco';
    candidate.options[1].code = 'MOD-A02-SM';
    const ui = screen(async () => response([group('1', ['10'], '10'), candidate]));
    await ui.choose('10');
    const combo = ui.selects[1].tomselect;
    assert.equal(combo.settings.options.length, 2);
    assert.equal(combo.settings.optgroups[0].value, '2');
    assert.equal(combo.getValue(), '');
    assert.equal(combo.query, undefined);
    assert.equal(combo.settings.score('CON marco')(combo.options['20']), 1);
    assert.equal(combo.settings.score('con marco')(combo.options['21']), 0);
    assert.equal(combo.settings.score('MOD-A02-SM')(combo.options['21']), 1);
    assert.equal(combo.settings.score('')(combo.options['20']), 1);
    assert.equal(combo.settings.score('')(combo.options['21']), 1);
});

test('a delayed response cannot overwrite a more recent general selection', async () => {
    const requests = [];
    const ui = screen(() => new Promise((resolve) => requests.push(resolve)));
    const first = ui.choose('10');
    const second = ui.choose('30');
    requests[1](response([group('3', ['30'], '30')]));
    await second;
    requests[0](response([group('1', ['10'], '10')]));
    await first;
    assert.equal(ui.filters[0].value, '3');
    assert.equal(ui.selects[0].tomselect.getValue(), '30');
});

test('a failed general search preserves the manual choices', async () => {
    const ui = screen(async () => ({ ok: false }));
    ui.selects[0].tomselect.setValue('99');
    await ui.choose('10');
    assert.equal(ui.selects[0].tomselect.getValue(), '99');
    assert.match(ui.status.textContent, /selecciones anteriores se conservan/);
});

test('manual changes during a pending general search prevent its response from overwriting the user', async () => {
    let complete;
    const ui = screen(() => new Promise((resolve) => { complete = resolve; }));
    const pending = ui.choose('10');
    ui.selects[0].tomselect.setValue('99');
    complete(response([group('1', ['10'], '10')]));
    await pending;
    assert.equal(ui.selects[0].tomselect.getValue(), '99');
    assert.match(ui.status.textContent, /búsqueda se interrumpió/);
});
