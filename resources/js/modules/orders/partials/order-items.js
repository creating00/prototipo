import { fetchProduct } from "./order-fetch";
import { Toast } from "@/config/notifications";
import {
    addRow as addRowRow,
    updateQuantity,
    updateSubtotal,
} from "./order-row";
import { getCurrentBranchId } from "../../../config/datatables";

export default {
    table: null,

    init() {
        if (this._initialized) return;
        this._initialized = true;

        this.table = document.querySelector("#order-items-table tbody");
        if (!this.table) return;

        // Escucha el evento global. No importa quién lo dispare
        // (el autocomplete o el escáner), este método agregará el producto.
        document.addEventListener("product:searchByCode", (e) => {
            if (!e.detail?.code) return;
            this.addProductByCode(e.detail.code);
        });

        this.table.addEventListener("click", (e) => {
            const btn = e.target.closest(".btn-remove-item");
            if (btn) this.removeRow(btn.closest("tr"));
        });

        this.updateRowIndices();
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
            const branchId = getCurrentBranchId();

            if (!branchId) {
                // Error local: No hay sucursal
                const msg =
                    "No se ha seleccionado una sucursal. Por favor, seleccione una sucursal primero.";
                Toast.fire({
                    icon: "warning",
                    title: "Atención",
                    text: msg,
                });
                throw new Error(msg);
            }

            // fetchProduct ya dispara su propio Toast (success o error)
            const { html } = await fetchProduct(code, branchId, "order");

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
            // Si hay error (404, red, etc), limpiamos el input para el siguiente escaneo
            this.clearInput();

            // Opcional: registrar en consola para debug personal
            console.debug("Operación de producto detenida:", e.message);
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
        const input = document.querySelector("#product_search_input");
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
