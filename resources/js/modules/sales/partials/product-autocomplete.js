import { getCurrentBranchId } from "../../../config/datatables";
import {
    formatRepairAmount,
    getRepairCategoryId,
    getSelectedRepairAmount,
    isRepairSaleSelected,
} from "@/helpers/repair-category";
import AutocompleteBase from "../../../helpers/autocomplete-base";

function setupFiltersChangeListener(autocomplete, moduleRef) {
    document.addEventListener("change", (e) => {
        if (
            e.target &&
            e.target.matches(
                'select[name="branch_id"], select[name="branch_recipient_id"], select[name="repair_type_id"], input[name="repair_type_id"], input[name="sale_type"], #repair_type'
            )
        ) {
            autocomplete.cancelSearch();
            autocomplete.cache = {};
            autocomplete.hideResults();

            // Actualizar el placeholder visualmente
            moduleRef.updatePlaceholder();

            const query = autocomplete.input?.value.trim() || "";
            if (query.length >= 2) {
                autocomplete.search(query);
            }
        }
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
        this.setupExtraActions();
    },

    updatePlaceholder() {
        const input = this.instance.input;
        const indicator = document.getElementById("search-filter-indicator");
        if (!input) return;

        const repairInput =
            document.querySelector('input[name="repair_type_id"]:checked') ||
            document.getElementById("repair_type") ||
            document.querySelector('select[name="repair_type_id"]');

        const categoryId = isRepairSaleSelected()
            ? getRepairCategoryId()
            : null;

        const selectedText =
            repairInput?.dataset?.label ||
            repairInput?.options?.[repairInput.selectedIndex]?.text ||
            repairInput?.nextElementSibling?.textContent?.trim();

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
        const isRepair = isRepairSaleSelected();

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
            const repairAmount = isRepairSaleSelected()
                ? getSelectedRepairAmount()
                : null;
            clone.querySelector(".product-price").textContent =
                repairAmount !== null
                    ? formatRepairAmount(repairAmount)
                    : product.price_display || `$${product.price}`;

            const costBadge = clone.querySelector(".product-cost");
            if (costBadge) {
                if (product.show_cost && product.cost_display) {
                    costBadge.innerHTML = `<i class="fas fa-tag me-1"></i>Costo: ${product.cost_display}`;
                    costBadge.classList.remove("d-none");
                } else {
                    costBadge.classList.add("d-none");
                }
            }

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

        const isRepair = isRepairSaleSelected();

        // Definimos el objeto que vamos a enviar
        const eventDetail = {
            code: code,
            context: this.context,
            is_repair: isRepair,
        };

        //console.log("Despachando producto:", eventDetail);

        document.dispatchEvent(
            new CustomEvent("product:searchByCode", {
                detail: eventDetail, // Asegúrate de usar el nombre de la variable definida arriba
            }),
        );

        this.instance.input.value = "";
        this.instance.hideResults();
    },

    showEmptyState() {
        const list = this.instance.resultsList;
        if (!list) return;

        const query = (this.instance.input?.value || "").trim();

        list.innerHTML = `
            <li class="p-3 text-center">
                <div class="text-muted mb-2">
                    <i class="fas fa-exclamation-circle text-warning fa-2x mb-1 d-block"></i>
                    <div style="font-size: 0.9rem;">No se encontró <strong>"${query || "el producto"}"</strong></div>
                </div>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm mt-1" id="btn-trigger-quick-product">
                    <i class="fas fa-plus-circle me-1"></i> Registrar Producto Nuevo
                </button>
            </li>
        `;
        this.instance.showResults();

        const btnTrigger = list.querySelector("#btn-trigger-quick-product");
        if (btnTrigger) {
            btnTrigger.addEventListener("click", (e) => {
                e.preventDefault();
                this.instance.hideResults();
                const modalEl = document.getElementById("modalQuickProduct");
                if (modalEl) {
                    const form = modalEl.querySelector("#formQuickProduct");
                    if (form) {
                        const nameInput = form.querySelector('input[name="name"]');
                        const codeInput = form.querySelector('input[name="code"]');
                        if (nameInput) nameInput.value = query;
                        if (codeInput && !codeInput.value) {
                            codeInput.value = "PRD-" + Math.floor(1000 + Math.random() * 9000);
                        }
                    }
                    const modal = window.bootstrap?.Modal?.getOrCreateInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
        }
    },

    setupExtraActions() {
        const btnSearch = document.querySelector("#btn-search-product");
        if (btnSearch) {
            btnSearch.addEventListener("click", () => {
                document.dispatchEvent(
                    new CustomEvent("product:openAdvancedSearch"),
                );
            });
        }

        if (document.datasetQuickProductBound) return;
        document.datasetQuickProductBound = true;

        document.addEventListener("submit", async (e) => {
            const formQuick = e.target.closest("#formQuickProduct");
            if (!formQuick) return;

            e.preventDefault();
            e.stopPropagation();

            const btnSave = document.getElementById("btnSaveQuickProduct");
            const originalHtml = btnSave ? btnSave.innerHTML : "";

            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
            }

            const formData = new FormData(formQuick);

            // Si no hay branch_id especificado en el formulario, intentar obtener del selector de la orden
            if (!formData.get('branch_id')) {
                const branchSelect = document.querySelector('select[name="customer_id"]') || document.querySelector('select[name="branch_id"]');
                if (branchSelect?.value) {
                    formData.set('branch_id', branchSelect.value);
                }
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ||
                                  document.querySelector('input[name="_token"]')?.value || "";

                const response = await fetch("/api/products", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: formData
                });

                const data = await response.json();
                if ((response.ok || response.status === 201) && data.code) {
                    const modalEl = document.getElementById("modalQuickProduct");
                    if (modalEl) {
                        const modal = window.bootstrap?.Modal?.getInstance(modalEl) || window.bootstrap?.Modal?.getOrCreateInstance(modalEl);
                        if (modal) modal.hide();
                    }
                    formQuick.reset();
                    this.selectProduct(data.code);
                } else {
                    const errorMsg = data.messages
                        ? Object.values(data.messages).flat().join("\n")
                        : (data.error || "Error al crear el producto.");
                    alert(errorMsg);
                }
            } catch (err) {
                console.error(err);
                alert("Error de conexión al guardar el producto.");
            } finally {
                if (btnSave) {
                    btnSave.disabled = false;
                    btnSave.innerHTML = originalHtml || '<i class="fas fa-check-circle me-1"></i> Guardar y Agregar al Pedido';
                }
            }
        });
    },
};
