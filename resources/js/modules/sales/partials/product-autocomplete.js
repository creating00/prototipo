import { getCurrentBranchId } from "../../../config/datatables";
import AutocompleteBase from "../../../helpers/autocomplete-base";

export default {
    instance: null,
    templateEmpty: null,

    init() {
        this.templateEmpty = document.querySelector("#tpl-search-empty");

        this.instance = new AutocompleteBase({
            inputSelector: "#product_search_input",
            resultsListSelector: "#search-results-list",
            spinnerSelector: "#search-spinner",
            templateSelector: "#tpl-search-item",
        });

        if (this.instance.input) {
            // Desvincular el evento base para usar nuestra lógica de delay (Barcode)
            this.instance.input.removeEventListener(
                "input",
                this.instance.handleInput
            );

            this.instance.input.addEventListener("input", () =>
                this.handleCustomInput()
            );

            this.instance.search = (query) => this.search(query);
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
            this.renderResults(this.instance.cache[query]);
            return;
        }

        // Lógica específica: barcode (rápido) vs nombre (normal)
        const delay = query.length > 7 ? 100 : 250;
        this.instance.debounceTimeout = setTimeout(
            () => this.search(query),
            delay
        );
    },

    async search(query) {
        this.instance.cancelSearch();
        const branchId = getCurrentBranchId();
        if (!branchId) return;

        this.instance.abortController = new AbortController();
        if (this.instance.spinner)
            this.instance.spinner.style.display = "block";

        try {
            const url = `/api/inventory/list?q=${encodeURIComponent(
                query
            )}&branch_id=${branchId}`;
            const response = await fetch(url, {
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
                this.selectProduct(product.code);
            });

            list.appendChild(clone);
        });

        this.instance.showResults();
    },

    selectProduct(code) {
        this.instance.cancelSearch();
        document.dispatchEvent(
            new CustomEvent("product:searchByCode", { detail: { code } })
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
                    new CustomEvent("product:openAdvancedSearch")
                );
            });
        }
    },
};
