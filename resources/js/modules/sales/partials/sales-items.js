import { fetchProduct } from "../../../modules/orders/partials/order-fetch";
import { Toast } from "@/config/notifications";
import {
    addRow as addRowRow,
    updateQuantity,
    updateSubtotal,
} from "../../../modules/orders/partials/order-row";
import { getCurrentBranchId } from "../../../config/datatables";

export default {
    table: null,
    init() {
        this.table = document.querySelector("#order-items-table tbody");

        if (this.table) {
            this.table.addEventListener("click", (e) => {
                const btnRemove = e.target.closest(".btn-remove-item");
                if (btnRemove) this.removeRow(btnRemove.closest("tr"));

                const btnEdit = e.target.closest(".btn-edit-price");
                if (btnEdit) this.togglePriceEdit(btnEdit.closest("tr"));
            });

            this.table.addEventListener("input", (e) => {
                if (e.target.classList.contains("unit-price")) {
                    this.handlePriceChange(e.target);
                }
            });

            // RECEPTOR: Escucha el evento del nuevo product-autocomplete.js
            document.addEventListener("product:searchByCode", (e) => {
                this.addProductByCode(e.detail.code);
            });

            this.updateRowIndices();
            this.updateTotal();
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
    preload(items = []) {
        items.forEach((item) => {
            if (item.html) {
                this.addRow(item.html);
            }
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
            const { html } = await fetchProduct(code, branchId, "sale");

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
    handlePriceChange(input) {
        const row = input.closest("tr");
        let val = parseFloat(input.value);

        if (val < 0 || isNaN(val)) {
            input.classList.add("is-invalid", "border-danger");
        } else if (val === 0) {
            input.classList.add("border-warning");
            input.classList.remove("is-invalid", "border-danger");
        } else {
            input.classList.remove(
                "is-invalid",
                "border-danger",
                "border-warning"
            );
        }

        const qty = parseFloat(row.querySelector(".quantity").value || 0);
        const price = isNaN(val) ? 0 : val;
        const subtotalInput = row.querySelector(".subtotal");

        if (subtotalInput) {
            subtotalInput.value = (qty * price).toFixed(2);
        }
        this.updateTotal();
    },
    togglePriceEdit(row) {
        const btn = row.querySelector(".btn-edit-price");
        const icon = btn.querySelector("i");
        const priceInput = row.querySelector(".unit-price");

        if (!priceInput || !btn) return;

        const isLocked = btn.dataset.status === "off";

        if (isLocked) {
            // Guardar valor actual como respaldo
            priceInput.dataset.originalValue = priceInput.value;

            priceInput.removeAttribute("readonly");
            priceInput.classList.add("bg-white");
            btn.dataset.status = "on";
            btn.classList.replace("btn-outline-warning", "btn-warning");
            icon.classList.replace("fa-lock", "fa-lock-open");

            priceInput.focus();
            priceInput.select();
        } else {
            let val = parseFloat(priceInput.value);
            const original = parseFloat(priceInput.dataset.originalValue || 0);

            // Validación: No permite <= 0. Si sucede, recupera el original.
            if (isNaN(val) || val <= 0) {
                priceInput.value = original.toFixed(2);

                Toast.fire({
                    icon: "warning",
                    title: "Precio inválido",
                    text: "Se ha restaurado el precio anterior (debe ser mayor a 0).",
                });

                this.handlePriceChange(priceInput);
            }

            priceInput.setAttribute("readonly", true);
            priceInput.classList.remove(
                "bg-white",
                "is-invalid",
                "border-danger",
                "border-warning"
            );
            btn.dataset.status = "off";
            btn.classList.replace("btn-warning", "btn-outline-warning");
            icon.classList.replace("fa-lock-open", "fa-lock");
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
    // En sale-items.js, asegúrate de que updateTotal sea así:
    updateTotal() {
        if (!this.table) return;

        const subtotal = Array.from(
            this.table.querySelectorAll(".subtotal")
        ).reduce((sum, input) => sum + parseFloat(input.value || 0), 0);

        const subtotalInput = document.getElementById("subtotal_amount");
        const subtotalDisplay = document.getElementById(
            "subtotal_amount_display"
        );

        if (subtotalInput) {
            subtotalInput.value = subtotal.toFixed(2);
        }

        if (subtotalDisplay) {
            subtotalDisplay.textContent = subtotal.toFixed(2);
        }

        document.dispatchEvent(
            new CustomEvent("sale:subtotalUpdated", {
                detail: { subtotal },
            })
        );
    },
    removeRow(row) {
        row.remove();
        this.updateRowIndices();
        this.updateTotal();
    },

    clearInput() {
        // ACTUALIZADO: Apunta al ID del nuevo buscador predictivo
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
