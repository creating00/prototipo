/**
 * Helper para gestión de tablas dinámicas de pedidos
 */
export const OrderUIHelper = {
    /**
     * Calcula el subtotal de una fila y actualiza el gran total del contenedor
     */
    calculateTotals(containerSelector, totalLabelSelector) {
        const container = document.querySelector(containerSelector);
        const orderTotalLabel = document.querySelector(totalLabelSelector);
        if (!container) return;

        let grandTotal = 0;

        container.querySelectorAll(".item-row").forEach((row) => {
            const qtyInput = row.querySelector(".qty-input");
            const costInput = row.querySelector(
                ".cost-input-container input:not([type='hidden'])"
            );

            const qty = parseFloat(qtyInput?.value || 0);
            const cost = parseFloat(costInput?.value || 0);
            const subtotal = qty * cost;

            const subtotalEl = row.querySelector(".row-subtotal");
            if (subtotalEl) subtotalEl.textContent = `$ ${subtotal.toFixed(2)}`;
            grandTotal += subtotal;
        });

        if (orderTotalLabel) {
            orderTotalLabel.textContent = `$ ${grandTotal.toFixed(2)}`;
        }
    },

    /**
     * Verifica si un valor ya existe en otros selectores similares
     */
    isDuplicate(container, currentSelect, value) {
        let duplicate = false;
        container.querySelectorAll(".product-select").forEach((select) => {
            if (select !== currentSelect) {
                const val = select._choices
                    ? select._choices.getValue(true)
                    : select.value;
                if (val && String(val) === String(value)) duplicate = true;
            }
        });
        return duplicate;
    },
};
