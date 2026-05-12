import AutocompleteBase from "../../../helpers/autocomplete-base";
import { ModalSuccessWatcher } from "../../../helpers/ModalSuccessWatcher";

export default {
    instance: null,
    countDisplay: null,
    tableBody: null,
    templateEmpty: null,
    selectedProviders: [],
    rowTemplate: null,

    init() {
        this.selectedProviders = [];

        this.countDisplay = document.querySelector("#provider-main-count");
        this.tableBody = document.querySelector("#provider-table-body");

        this.rowTemplate = document.querySelector("#tpl-selected-provider-row");
        this.templateEmpty = document.querySelector("#tpl-search-empty");

        this.instance = new AutocompleteBase({
            inputSelector: "#provider_search_input",
            resultsListSelector: "#provider-results-list",
            spinnerSelector: "#provider-search-spinner",
            templateSelector: "#tpl-provider-item",
        });

        if (this.instance.input) {
            this.instance.input.removeEventListener(
                "input",
                this.instance.handleInput
            );
            this.instance.input.addEventListener("input", () =>
                this.handleCustomInput()
            );
            this.instance.search = (query) => this.executeSearch(query);

            // Listener global para capturar proveedores creados desde cualquier parte (p.e. el modal)
            document.addEventListener("provider:created", (e) => {
                const newProvider = e.detail?.data || e.detail;
                if (newProvider) this.selectProvider(newProvider);
            });

            this.setupExtraActions();
        }

        if (this.tableBody) this.tableBody.innerHTML = "";
    },

    handleCustomInput() {
        clearTimeout(this.instance.debounceTimeout);
        const query = this.instance.input.value.trim();

        if (query.length < 2) {
            this.instance.cancelSearch();
            this.instance.hideResults();
            return;
        }

        if (this.instance.cache[query]) {
            this.renderResults(this.instance.cache[query]);
            return;
        }

        this.instance.debounceTimeout = setTimeout(
            () => this.executeSearch(query),
            250
        );
    },

    async executeSearch(query) {
        this.instance.cancelSearch();
        this.instance.abortController = new AbortController();
        if (this.instance.spinner)
            this.instance.spinner.style.display = "block";

        try {
            const response = await fetch(
                `/api/providers/search?q=${encodeURIComponent(query)}`,
                {
                    headers: { Accept: "application/json" },
                    signal: this.instance.abortController.signal,
                }
            );
            const providers = await response.json();
            this.instance.cache[query] = providers;
            this.renderResults(providers);
        } catch (error) {
            if (error.name !== "AbortError") this.renderResults([]);
        } finally {
            if (this.instance.spinner)
                this.instance.spinner.style.display = "none";
        }
    },

    renderResults(providers) {
        const list = this.instance.resultsList;
        if (!list || this.instance.input.value.trim() === "") return;

        list.innerHTML = "";
        this.instance.currentIndex = providers.length > 0 ? 0 : -1;
        this.instance.currentResults = providers;

        if (providers.length === 0) {
            this.showEmptyState();
            return;
        }

        providers.forEach((provider, index) => {
            const clone = this.instance.template.content.cloneNode(true);
            const link = clone.querySelector(".dropdown-item");

            if (index === 0) link.classList.add("active");

            link.querySelector(".provider-biz-name").textContent =
                provider.business_name;
            link.querySelector(".provider-tax-id").textContent =
                provider.tax_id;

            link.addEventListener("click", (e) => {
                e.preventDefault();
                this.selectProvider(provider);
            });
            list.appendChild(clone);
        });
        this.instance.showResults();
    },

    selectProvider(provider) {
        if (!provider) return;

        // Normalizar ID para evitar fallos de comparación (1 vs "1")
        const providerId = Number(provider.id);

        if (!this.selectedProviders.some((p) => Number(p.id) === providerId)) {
            // Clonamos el objeto para evitar mutaciones inesperadas
            this.selectedProviders.push({ ...provider, id: providerId });
            this.renderTable();
        }

        this.instance.input.value = "";
        this.instance.hideResults();
        this.instance.currentIndex = -1;
    },

    renderTable() {
        if (this.countDisplay)
            this.countDisplay.textContent = this.selectedProviders.length;
        if (!this.tableBody || !this.rowTemplate) return;

        this.tableBody.innerHTML = "";

        this.selectedProviders.forEach((p) => {
            const clone = this.rowTemplate.content.cloneNode(true);
            const row = clone.querySelector("tr");

            row.querySelector(".col-name").textContent = p.business_name;
            row.querySelector(".col-tax-id").textContent = p.tax_id;
            row.querySelector(".col-phone").textContent = p.phone ?? "-";

            const input = row.querySelector('input[name="providers[]"]');
            input.value = p.id;

            const btnRemove = row.querySelector(".remove-provider");
            btnRemove.addEventListener("click", () => {
                // 2. Eliminar por ID en lugar de index para mayor seguridad
                this.selectedProviders = this.selectedProviders.filter(
                    (item) => item.id !== p.id
                );
                this.renderTable();
            });

            this.tableBody.appendChild(clone);
        });
    },

    showEmptyState() {
        if (this.templateEmpty) {
            const clone = this.templateEmpty.content.cloneNode(true);
            this.instance.resultsList.appendChild(clone);
            this.instance.showResults();
        }
    },

    setupExtraActions() {
        this.instance.input.addEventListener("keydown", (e) => {
            if (e.key === "Enter" && this.instance.currentIndex !== -1) {
                e.preventDefault();
                const provider =
                    this.instance.currentResults[this.instance.currentIndex];
                if (provider) this.selectProvider(provider);
            }
        });

        const btnAdd = document.querySelector("#btn-quick-add-provider");
        if (btnAdd) {
            btnAdd.addEventListener("click", () => {
                const modalId = "modalProvider";

                // Pasamos 'provider:created' como tercer argumento
                ModalSuccessWatcher.watch(
                    modalId,
                    (data) => {
                        if (data) {
                            this.selectProvider(data);
                        }
                    },
                    "provider:created"
                );

                const modalElement = document.getElementById(modalId);
                if (modalElement) {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        }
    },
};
