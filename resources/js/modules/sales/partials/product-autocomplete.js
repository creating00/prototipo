import { getCurrentBranchId } from "../../../config/datatables";
import { getRepairCategoryId } from "@/helpers/repair-category";
import AutocompleteBase from "../../../helpers/autocomplete-base";

function setupFiltersChangeListener(autocomplete, moduleRef) {
    const selectors = [
        'select[name="branch_id"]',
        'select[name="branch_recipient_id"]',
        'select[name="repair_type_id"]',
        "#repair_type",
    ];

    selectors.forEach((selector) => {
        const el = document.querySelector(selector);
        if (!el) return;

        el.addEventListener("change", () => {
            autocomplete.cancelSearch();
            autocomplete.cache = {};
            autocomplete.hideResults();

            // Actualizar el placeholder visualmente
            moduleRef.updatePlaceholder();

            const query = autocomplete.input.value.trim();
            if (query.length >= 2) {
                autocomplete.search(query);
            }
        });
    });
}

export default {
    instance: null,
    templateEmpty: null,
    context: "sale",

    init(options = {}) {
        if (options.context) this.context = options.context;

        this.instance = new AutocompleteBase({
            inputSelector: "#product_search_input",
            resultsListSelector: "#search-results-list",
            spinnerSelector: "#search-spinner",
            templateSelector: "#tpl-search-item",
        });

        if (this.instance.input) {
            // ERROR CORREGIDO: Sobrescribir renderResults de la instancia
            // y vincularlo al contexto de este objeto literal.
            this.instance.renderResults = (products) =>
                this.renderResults(products);

            this.instance.input.removeEventListener(
                "input",
                this.instance.handleInput,
            );

            // Usar Arrow Function para que 'this' dentro de handleCustomInput sea este objeto
            this.instance.input.addEventListener("input", () =>
                this.handleCustomInput(),
            );

            this.instance.search = (query) => this.search(query);

            this.updatePlaceholder();

            document.addEventListener("sale:typeChanged", () => {
                this.instance.clear();
                this.instance.cache = {};
                this.updatePlaceholder();
            });
        }

        setupFiltersChangeListener(this.instance, this);
    },

    updatePlaceholder() {
        const input = this.instance.input;
        const indicator = document.getElementById("search-filter-indicator");
        if (!input) return;

        const repairSelect =
            document.getElementById("repair_type") ||
            document.querySelector('select[name="repair_type_id"]');

        const isRepairMode = repairSelect && !repairSelect.disabled;
        const categoryId = isRepairMode ? getRepairCategoryId() : null;

        const selectedText =
            repairSelect?.options[repairSelect.selectedIndex]?.text;

        if (
            categoryId &&
            selectedText &&
            selectedText !== "Seleccione tipo de reparación"
        ) {
            input.placeholder = `Buscando en ${selectedText}...`;
            input.style.borderColor = "#0dcaf0";

            if (indicator) {
                indicator.innerHTML = `
                <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle shadow-sm" 
                      style="font-size: 0.65rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.025em;">
                    <i class="fas fa-filter me-1"></i>Filtro: ${selectedText}
                </span>`;
            }
        } else {
            // RESET TOTAL: Limpia el placeholder y el SPAN
            input.placeholder = "Escriba código o nombre...";
            input.style.borderColor = "";
            if (indicator) indicator.innerHTML = "";
        }
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
            // Ahora 'this' es correcto y renderResults está disponible
            this.renderResults(this.instance.cache[query]);
            return;
        }

        const delay = query.length > 7 ? 100 : 250;
        this.instance.debounceTimeout = setTimeout(
            () => this.search(query),
            delay,
        );
    },

    async search(query) {
        this.instance.cancelSearch();

        const branchId = getCurrentBranchId();
        const categoryId = getRepairCategoryId();

        // Detectar si estamos en modo reparación basado en el select
        const repairSelect =
            document.getElementById("repair_type") ||
            document.querySelector('select[name="repair_type_id"]');
        const isRepair = !!(
            repairSelect &&
            !repairSelect.disabled &&
            categoryId
        );

        if (!branchId) return;

        this.instance.abortController = new AbortController();
        if (this.instance.spinner)
            this.instance.spinner.style.display = "block";

        try {
            const url = new URL("/api/inventory/list", window.location.origin);
            url.searchParams.append("q", query);
            url.searchParams.append("branch_id", branchId);

            // Enviamos el contexto y el estado de reparación
            url.searchParams.append("context", this.context);
            url.searchParams.append("is_repair", isRepair ? "1" : "0");

            if (categoryId) {
                url.searchParams.append("category_id", categoryId);
            }

            const response = await fetch(url.toString(), {
                headers: { Accept: "application/json" },
                signal: this.instance.abortController.signal,
            });

            const data = await response.json();
            const products = Array.isArray(data) ? data : data.data || [];

            this.instance.cache[query] = products;
            this.renderResults(products);
        } catch (error) {
            if (error.name === "AbortError") return;
            this.renderResults([]);
        } finally {
            if (this.instance.spinner)
                this.instance.spinner.style.display = "none";
        }
    },

    renderResults(products) {
        const list = this.instance.resultsList;
        if (!list) return;

        if (this.instance.input.value.trim() === "") {
            this.instance.hideResults();
            return;
        }

        list.innerHTML = "";
        this.instance.currentIndex = products.length > 0 ? 0 : -1;

        if (products.length === 0) {
            this.showEmptyState();
            return;
        }

        products.forEach((product, index) => {
            const clone = this.instance.template.content.cloneNode(true);
            const link = clone.querySelector(".dropdown-item");
            if (index === 0) link.classList.add("active");

            link.dataset.code = product.code;
            clone.querySelector(".product-name").textContent = product.name;
            clone.querySelector(".product-meta").textContent = `Código: ${
                product.code
            } | Stock: ${product.stock ?? 0}`;
            clone.querySelector(".product-price").textContent =
                product.price_display || `$${product.price}`;

            link.addEventListener("click", (e) => {
                e.preventDefault();
                // Usar arrow function o bind para asegurar que 'this' sea el objeto exportado
                this.selectProduct(product.code);
            });

            list.appendChild(clone);
        });

        this.instance.showResults();
    },

    // resources/js/modules/sales/partials/product-autocomplete.js

    selectProduct(code) {
        this.instance.cancelSearch();

        const categoryId = getRepairCategoryId();
        const repairSelect =
            document.getElementById("repair_type") ||
            document.querySelector('select[name="repair_type_id"]');

        const isRepair = !!(
            repairSelect &&
            !repairSelect.disabled &&
            categoryId
        );

        // Definimos el objeto que vamos a enviar
        const eventDetail = {
            code: code,
            context: this.context,
            is_repair: isRepair,
        };

        console.log("Despachando producto:", eventDetail);

        document.dispatchEvent(
            new CustomEvent("product:searchByCode", {
                detail: eventDetail, // Asegúrate de usar el nombre de la variable definida arriba
            }),
        );

        this.instance.input.value = "";
        this.instance.hideResults();
    },

    showEmptyState() {
        if (this.templateEmpty) {
            const clone = this.templateEmpty.content.cloneNode(true);
            this.instance.resultsList.appendChild(clone);
            this.instance.showResults();
        }
    },

    setupExtraActions() {
        const btnSearch = document.querySelector("#btn-search-product");
        if (btnSearch) {
            btnSearch.addEventListener("click", () => {
                // Notificamos que se requiere abrir la búsqueda avanzada
                document.dispatchEvent(
                    new CustomEvent("product:openAdvancedSearch"),
                );
            });
        }
    },
};
