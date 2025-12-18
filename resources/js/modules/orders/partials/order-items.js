import { fetchProduct } from "./order-fetch";
import {
    addRow as addRowRow,
    updateQuantity,
    updateSubtotal,
} from "./order-row";
import { getCurrentBranchId } from "../../../config/datatables";

export default {
    table: null,

    init() {
        this.table = document.querySelector("#order-items-table tbody");

        if (this.table) {
            this.table.addEventListener("click", (e) => {
                const btn = e.target.closest(".btn-remove-item");
                if (btn) this.removeRow(btn.closest("tr"));
            });

            this.updateRowIndices();
        }
    },

    addRow(html) {
        if (!this.table) {
            this.table = document.querySelector("#order-items-table tbody");
        }

        return addRowRow(this.table, html, {
            updateTotal: () => this.updateTotal(),
        });
    },

    async addProductByCode(code) {
        try {
            // Obtener el branch_id actual
            const branchId = getCurrentBranchId();

            if (!branchId) {
                throw new Error(
                    "No se ha seleccionado una sucursal. Por favor, seleccione una sucursal primero."
                );
            }

            const { html } = await fetchProduct(code, branchId);
            this.clearInput();

            const row = this.findRow(code);

            if (row) {
                updateQuantity(
                    row,
                    parseInt(row.querySelector(".quantity").value) + 1,
                    {
                        updateTotal: () => this.updateTotal(),
                    }
                );
            } else {
                this.addRow(html);
            }

            this.updateRowIndices();
            this.updateTotal();
        } catch (e) {
            console.error("Error al agregar producto:", e);
            alert(e.message || "Error al buscar el producto.");
        }
    },

    findRow(code) {
        if (!this.table) {
            this.table = document.querySelector("#order-items-table tbody");
        }

        if (!this.table) return null;

        // Normaliza el código (elimina espacios, convierte a mayúsculas)
        const normalizedCode = code.toString().trim().toUpperCase();

        // Busca en todas las filas
        const rows = this.table.querySelectorAll("tr[data-code]");
        for (let row of rows) {
            const rowCode = row.dataset.code?.toString().trim().toUpperCase();
            if (rowCode === normalizedCode) {
                return row;
            }
        }

        return null;
    },

    updateTotal() {
        if (!this.table) {
            this.table = document.querySelector("#order-items-table tbody");
        }

        if (!this.table) return;

        const total = Array.from(
            this.table.querySelectorAll(".subtotal")
        ).reduce((sum, input) => sum + parseFloat(input.value || 0), 0);

        const totalInput = document.querySelector("#total_amount");
        if (totalInput) {
            totalInput.value = total.toFixed(2);
        }

        const event = new CustomEvent("sale:totalUpdated", {
            detail: { total: total },
        });
        document.dispatchEvent(event);
    },

    removeRow(row) {
        row.remove();
        this.updateRowIndices();
        this.updateTotal();
    },

    clearInput() {
        const input = document.querySelector("#product_search_code");
        if (input) {
            input.value = "";
            input.focus();
        }
    },

    updateRowIndices() {
        if (!this.table) {
            this.table = document.querySelector("#order-items-table tbody");
        }

        if (!this.table) return;

        this.table.querySelectorAll("tr").forEach((row, index) => {
            row.dataset.index = index;
            row.querySelectorAll("input, select").forEach((input) => {
                if (input.name) {
                    input.name = input.name.replace(
                        /items\[(\d+|INDEX)\]/,
                        `items[${index}]`
                    );
                }
            });
        });
    },
};
