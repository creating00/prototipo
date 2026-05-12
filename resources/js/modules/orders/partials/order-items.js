// resources/js/modules/orders/partials/order-items.js
import { fetchProduct } from "./order-fetch";
import { Toast } from "@/config/notifications";
import { addRow as addRowRow, updateQuantity } from "./order-row";
import { getCurrentBranchId } from "../../../config/datatables";
import TableUiManager from "../../sales/services/TableUiManager";

export default {
    table: null,
    context: "order",

    init() {
        this.table = document.querySelector("#order-items-table tbody");
        if (!this.table) return;

        this.bindEvents();
        this.refreshTableState();
    },

    bindEvents() {
        // Usar arrow function para preservar el scope de 'this'
        document.addEventListener("product:searchByCode", (e) => {
            if (e.detail && e.detail.context === this.context) {
                this.addProductByCode(e.detail);
            }
        });

        this.table.addEventListener("click", (e) => {
            const btn = e.target.closest(".btn-remove-item");
            if (btn) this.removeRow(btn.closest("tr"));
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
            // Obtener datos del producto
            const response = await fetchProduct(
                code,
                branchId,
                context,
                is_repair,
            );

            // Validar que la respuesta tenga el HTML necesario
            if (!response || !response.html) {
                console.error("No se recibió HTML del servidor");
                return;
            }

            this.clearInput();

            const row = this.findRow(code);
            if (row) {
                const qtyInput = row.querySelector(".quantity");
                const currentQty = parseInt(qtyInput.value) || 0;

                updateQuantity(row, currentQty + 1, {
                    updateTotal: () => this.updateTotal(),
                });
            } else {
                // Insertar nueva fila
                this.addRow(response.html);
            }

            this.refreshTableState();
        } catch (e) {
            console.error("Error al procesar producto:", e);
            this.clearInput();
        }
    },

    findRow(code) {
        if (!this.table) return null;
        const normalized = code.toString().trim().toUpperCase();
        return this.table.querySelector(`tr[data-code="${normalized}"]`);
    },

    updateTotal() {
        if (!this.table) return;

        const subtotals = Array.from(this.table.querySelectorAll(".subtotal"));
        const total = subtotals.reduce(
            (sum, input) => sum + parseFloat(input.value || 0),
            0,
        );

        const totalInput = document.querySelector("#total_amount");
        if (totalInput) totalInput.value = total.toFixed(2);

        document.dispatchEvent(
            new CustomEvent("sale:totalUpdated", { detail: { total } }),
        );
    },

    addRow(html) {
        if (!this.table) {
            this.table = document.querySelector("#order-items-table tbody");
        }

        if (!this.table) return;

        return addRowRow(this.table, html, {
            updateTotal: () => this.updateTotal(),
        });
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

    clearInput() {
        const input = document.querySelector("#product_search_input");
        if (input) {
            input.value = "";
            input.focus();
        }
    },
};
