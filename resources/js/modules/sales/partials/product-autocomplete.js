import { getCurrentBranchId } from "../../../config/datatables";
import orderModal from "../../orders/partials/order-modal";

export default {
    input: null,
    resultsList: null,
    spinner: null,
    template: null,
    currentIndex: -1,
    debounceTimeout: null,
    abortController: null,
    cache: {},

    init() {
        this.input = document.querySelector("#product_search_input");
        this.resultsList = document.querySelector("#search-results-list");
        this.spinner = document.querySelector("#search-spinner");
        this.template = document.querySelector("#tpl-search-item");
        this.templateEmpty = document.querySelector("#tpl-search-empty");

        if (this.input) {
            this.setupEvents();
            this.setupExtraActions();
        }
    },

    setupEvents() {
        this.input.addEventListener("input", () => this.handleInput());
        this.input.addEventListener("keydown", (e) => this.handleNavigation(e));

        document.addEventListener("click", (e) => {
            if (
                !this.input.contains(e.target) &&
                !this.resultsList?.contains(e.target)
            ) {
                this.hideResults();
            }
        });
    },

    // Unificamos el botón de búsqueda manual (lupa) si existe
    setupExtraActions() {
        const btnSearch = document.querySelector("#btn-search-product");
        if (btnSearch) {
            btnSearch.addEventListener("click", () => this.openProductModal());
        }
    },

    openProductModal() {
        if (orderModal.dataTable) orderModal.reloadTable();
        $("#productSearchModal").modal("show");
    },

    handleInput() {
        clearTimeout(this.debounceTimeout);
        const query = this.input.value.trim();

        if (query.length < 2) {
            this.cancelSearch();
            this.hideResults();
            return;
        }

        // 1. Verificar Cache antes de ir al servidor
        if (this.cache[query]) {
            this.renderResults(this.cache[query]);
            return;
        }

        // 2. Si parece un código de barras (ej. más de 8 caracteres),
        // disparamos más rápido (100ms) que una búsqueda normal (250ms)
        const delay = query.length > 7 ? 100 : 250;
        this.debounceTimeout = setTimeout(() => this.search(query), delay);
    },

    async search(query) {
        this.cancelSearch();
        const branchId = getCurrentBranchId();
        if (!branchId) return;

        this.abortController = new AbortController();
        if (this.spinner) this.spinner.style.display = "block";

        try {
            const url = `/api/inventory/list?q=${encodeURIComponent(
                query
            )}&branch_id=${branchId}`;
            const response = await fetch(url, {
                headers: { Accept: "application/json" },
                signal: this.abortController.signal,
            });

            const data = await response.json();
            const products = Array.isArray(data) ? data : data.data || [];

            // 3. Guardar en cache para la próxima vez
            this.cache[query] = products;

            this.renderResults(products);
        } catch (error) {
            if (error.name === "AbortError") return;
            console.error("Autocomplete Error:", error);
            this.renderResults([]);
        } finally {
            if (this.spinner) this.spinner.style.display = "none";
        }
    },

    clearCache() {
        this.cache = {};
    },

    cancelSearch() {
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
    },

    renderResults(products) {
        if (!this.resultsList) return;

        // SEGURIDAD: Si el input está vacío, no renderizar nada.
        // Esto evita que una respuesta lenta de la API muestre resultados
        // después de que el escáner limpió el input.
        if (this.input.value.trim() === "") {
            this.hideResults();
            return;
        }

        this.resultsList.innerHTML = "";
        const items = Array.isArray(products) ? products : products.data || [];
        this.currentIndex = items.length > 0 ? 0 : -1;

        if (items.length === 0) {
            this.showEmptyState();
            return;
        }

        items.forEach((product, index) => {
            const clone = this.template.content.cloneNode(true);
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

            this.resultsList.appendChild(clone);
        });

        this.showResults();
    },

    handleNavigation(e) {
        if (e.key === "Enter" && this.currentIndex === -1) {
            // Si presiona Enter y no hay resultados desplegados,
            // intentamos buscar directamente el código escrito
            const code = this.input.value.trim();
            if (code) {
                e.preventDefault();
                this.selectProduct(code);
            }
            return;
        }

        const items = this.resultsList.querySelectorAll(".dropdown-item");
        if (!items.length) return;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            this.currentIndex = Math.min(
                this.currentIndex + 1,
                items.length - 1
            );
            this.highlightItem(items);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            this.currentIndex = Math.max(this.currentIndex - 1, 0);
            this.highlightItem(items);
        } else if (e.key === "Enter") {
            if (this.currentIndex >= 0) {
                e.preventDefault();
                items[this.currentIndex].click();
            }
        } else if (e.key === "Escape") {
            this.hideResults();
        }
    },

    highlightItem(items) {
        items.forEach((item, index) => {
            item.classList.toggle("active", index === this.currentIndex);
            if (index === this.currentIndex)
                item.scrollIntoView({ block: "nearest" });
        });
    },

    selectProduct(code) {
        this.cancelSearch(); // NUEVO: Cancelamos cualquier búsqueda en curso inmediatamente
        document.dispatchEvent(
            new CustomEvent("product:searchByCode", {
                detail: { code },
            })
        );
        this.input.value = "";
        this.hideResults();
    },

    showResults() {
        this.resultsList.style.display = "block";
        this.resultsList.classList.add("show");
    },

    hideResults() {
        if (this.resultsList) {
            this.resultsList.style.display = "none";
            this.resultsList.classList.remove("show");
        }
        this.currentIndex = -1;
    },

    showEmptyState() {
        if (this.templateEmpty) {
            const clone = this.templateEmpty.content.cloneNode(true);
            this.resultsList.appendChild(clone);
            this.showResults();
        }
    },
};
