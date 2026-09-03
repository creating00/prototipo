// resources/js/modules/sales/partials/sales-items.js
import { fetchProduct } from "../../../modules/orders/partials/order-fetch";
import { Toast } from "@/config/notifications";
import {
    addRow as addRowRow,
    updateSubtotal,
    updateQuantity,
} from "../../../modules/orders/partials/order-row";
import { getCurrentBranchId } from "../../../config/datatables";
import {
    formatRepairAmount,
    getSelectedRepairAmount,
    isRepairSaleSelected,
} from "@/helpers/repair-category";
import Totalizer from "../services/Totalizer";
import TableUiManager from "../services/TableUiManager";

export default {
    table: null,
    context: "sale",

    preload(items) {
        if (!items || !items.length) return;

        items.forEach((item) => {
            // Insertamos el HTML (que viene con "INDEX")
            this.addRow(item.html || item);
        });

        // Una vez cargados todos, transformamos los "INDEX" en [0], [1], [2]...
        this.refreshTableState();
    },
    init() {
        this.table = document.querySelector("#order-items-table tbody");
        if (!this.table) return;

        this.bindEvents();
        this.updateTotal();
    },

    bindEvents() {
        document.addEventListener("product:searchByCode", (e) => {
            if (e.detail && e.detail.context === this.context) {
                this.addProductByCode(e.detail);
            }
        });

        this.table.addEventListener("click", (e) => {
            const row = e.target.closest("tr");
            if (!row) return;
            if (e.target.closest(".btn-remove-item")) this.removeRow(row);
            if (e.target.closest(".btn-edit-price")) this.togglePriceEdit(row);
        });

        this.table.addEventListener("input", (e) => {
            if (e.target.classList.contains("unit-price")) {
                this.handlePriceChange(e.target);
            }
        });

        document.addEventListener("sale:typeChanged", () => this.clearTable());

        document.addEventListener("change", (e) => {
            if (e.target && e.target.name === "repair_type_id") {
                this.applySelectedRepairAmountToRows();
            }
        });
    },

    async addProductByCode(payload) {
        const { code, context, is_repair } = payload;
        const branchId = getCurrentBranchId();

        if (!branchId) {
            Toast.fire({
                icon: "warning",
                title: "Atención",
                text: "Seleccione sucursal.",
            });
            return;
        }

        try {
            const response = await fetchProduct(
                code,
                branchId,
                context,
                is_repair,
            );

            if (!response || !response.html) return;

            this.clearSearchInput();

            const existingRow = this.findRow(code);
            if (existingRow) {
                const qtyInput = existingRow.querySelector(".quantity");
                const currentQty = parseInt(qtyInput.value) || 0;

                updateQuantity(existingRow, currentQty + 1, {
                    updateTotal: () => this.updateTotal(),
                });
            } else {
                const row = this.addRow(response.html);
                this.applySelectedRepairAmountToRow(row);
            }

            this.refreshTableState();
        } catch (e) {
            this.clearSearchInput();
        }
    },

    togglePriceEdit(row) {
        const btn = row.querySelector(".btn-edit-price");
        const icon = btn?.querySelector("i");
        const priceInput = row.querySelector(".unit-price");

        if (!priceInput || !btn) return;

        const isLocked = btn.dataset.status === "off";

        if (isLocked) {
            // Guardar valor actual y habilitar edición
            priceInput.dataset.originalValue = priceInput.value;
            priceInput.removeAttribute("readonly");

            // Cambio visual: de gris (bloqueado) a blanco (editable)
            priceInput.classList.replace("bg-light", "bg-white");

            btn.dataset.status = "on";
            btn.classList.replace("btn-outline-warning", "btn-warning");
            icon?.classList.replace("fa-lock", "fa-lock-open");

            priceInput.focus();
            priceInput.select();
        } else {
            let val = parseFloat(priceInput.value);
            const original = parseFloat(priceInput.dataset.originalValue || 0);

            // Validación de precio
            if (isNaN(val) || val <= 0) {
                priceInput.value = original.toFixed(2);
                Toast.fire({
                    icon: "warning",
                    title: "Precio inválido",
                    text: "Se ha restaurado el precio anterior.",
                });
                this.handlePriceChange(priceInput);
            }

            // Bloquear campo
            priceInput.setAttribute("readonly", true);

            // Cambio visual: de blanco a gris
            priceInput.classList.replace("bg-white", "bg-light");

            // Limpiar estados de error/advertencia
            priceInput.classList.remove(
                "is-invalid",
                "border-danger",
                "border-warning",
            );

            btn.dataset.status = "off";
            btn.classList.replace("btn-warning", "btn-outline-warning");
            icon?.classList.replace("fa-lock-open", "fa-lock");
        }
    },

    handlePriceChange(input) {
        const row = input.closest("tr");
        if (!row) return;

        let val = parseFloat(input.value);

        // Feedback visual
        input.classList.toggle("is-invalid", val < 0 || isNaN(val));
        input.classList.toggle("border-danger", val < 0 || isNaN(val));
        input.classList.toggle("border-warning", val === 0);

        const qty = parseFloat(row.querySelector(".quantity")?.value || 0);
        const price = isNaN(val) ? 0 : val;
        const subtotalInput = row.querySelector(".subtotal");

        if (subtotalInput) {
            subtotalInput.value = (qty * price).toFixed(2);
        }
        this.updateTotal();
    },

    updateTotal() {
        if (!this.table) return;
        const isRepair = isRepairSaleSelected();
        Totalizer.updateSubtotal(this.table.querySelectorAll("tr"), isRepair);
    },

    findRow(code) {
        if (!this.table) return null;
        const normalized = code.toString().trim().toUpperCase();
        return this.table.querySelector(`tr[data-code="${normalized}"]`);
    },

    addRow(html) {
        if (!this.table)
            this.table = document.querySelector("#order-items-table tbody");
        if (!this.table) return;

        return addRowRow(this.table, html, {
            updateTotal: () => this.updateTotal(),
        });
    },

    applySelectedRepairAmountToRows() {
        if (!this.table) return;
        this.table
            .querySelectorAll("tr")
            .forEach((row) => this.applySelectedRepairAmountToRow(row));
        this.refreshTableState();
    },

    applySelectedRepairAmountToRow(row) {
        if (!row || !isRepairSaleSelected()) return;

        const repairAmount = getSelectedRepairAmount();
        if (repairAmount === null) return;

        const priceInput = row.querySelector(".unit-price");
        if (!priceInput) return;

        this.ensureCostCell(row, priceInput);

        priceInput.value = repairAmount.toFixed(2);

        const costInput = row.querySelector(".cost-display");
        if (costInput) {
            costInput.value = formatRepairAmount(repairAmount);
        }

        updateSubtotal(row);
    },

    ensureCostCell(row, priceInput) {
        if (row.querySelector(".cost-display")) return;
        if (!this.shouldShowCostColumn()) return;

        const priceCell = priceInput.closest("td");
        if (!priceCell) return;

        const costCell = document.createElement("td");
        costCell.innerHTML = `
            <div class="input-group">
                <span class="input-group-text bg-secondary text-white font-monospace">$</span>
                <input type="text" class="form-control bg-light text-muted fw-bold cost-display" readonly title="Costo unitario">
            </div>
        `;

        row.insertBefore(costCell, priceCell);
    },

    shouldShowCostColumn() {
        const table = this.table?.closest("table");
        if (!table) return false;

        return Array.from(table.querySelectorAll("thead th")).some(
            (header) => header.textContent.trim().toLowerCase() === "costo",
        );
    },

    removeRow(row) {
        if (!row) return;
        row.remove();
        this.refreshTableState();
    },

    refreshTableState() {
        if (!this.table) return;
        TableUiManager.updateIndices(this.table);
        this.updateTotal();
    },

    clearTable() {
        if (this.table) {
            this.table.innerHTML = "";
            this.refreshTableState();
        }
    },

    clearSearchInput() {
        const input = document.querySelector("#product_search_input");
        if (input) {
            input.value = "";
            input.focus();
        }
    },
};
