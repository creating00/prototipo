// resources/js/modules/sales/services/Totalizer.js
import ProductCalculator from "./ProductCalculator";

const Totalizer = {
    calculateRow: (price, qty) =>
        ProductCalculator.calculateRowSubtotal(price, qty),

    updateSubtotal: function (rows, isRepair) {
        // El subtotal SIEMPRE viene de la suma de las filas de la tabla
        const subtotal = ProductCalculator.calculateTableTotal(rows);
        const formatted = subtotal.toFixed(2);

        const subtotalInput = document.getElementById("subtotal_amount");
        const subtotalDisplay = document.getElementById(
            "subtotal_amount_display"
        );

        if (subtotalInput) {
            subtotalInput.value = formatted;
            subtotalInput.dispatchEvent(new Event("input"));
        }

        if (subtotalDisplay) {
            subtotalDisplay.textContent = formatted;
        }

        // Notificar a Summary y Payment
        document.dispatchEvent(
            new CustomEvent("sale:subtotalUpdated", {
                detail: { subtotal, isRepair },
            })
        );

        this.notifyTotalUpdate(subtotal, isRepair);

        return subtotal;
    },

    notifyTotalUpdate: function (total, isRepair) {
        document.dispatchEvent(
            new CustomEvent("sale:totalUpdated", {
                detail: { total: total, isRepair: isRepair },
            })
        );
    },
};

export default Totalizer;
