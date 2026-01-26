const saleSummary = {
    init() {
        this.bindEvents();
        this.readInitialValues();
    },

    bindEvents() {
        // Subtotal
        document.addEventListener("sale:subtotalUpdated", (e) => {
            this.updateSubtotal(e.detail.subtotal);
        });

        // Total
        document.addEventListener("sale:totalUpdated", (e) => {
            this.updateTotal(e.detail.total);
        });

        // Descuento
        document.addEventListener("sale:discountUpdated", (e) => {
            this.updateDiscount(e.detail.discount);
        });

        // Pago
        document.addEventListener("sale:paymentUpdated", (e) => {
            this.updatePaymentStatus(e.detail);
            this.updatePaymentValues(e.detail);
        });
    },

    readInitialValues() {
        const subtotalInput = document.getElementById("subtotal_amount");
        const discountInput = document.getElementById("discount_amount_input");
        const totalInput = document.getElementById("total_amount");

        if (subtotalInput) {
            this.updateSubtotal(parseFloat(subtotalInput.value) || 0);
        }

        if (discountInput) {
            this.updateDiscount(parseFloat(discountInput.value) || 0);
        }

        if (totalInput) {
            this.updateTotal(parseFloat(totalInput.value) || 0);
        }
    },

    updateSubtotal(value) {
        const el = document.getElementById("summary_subtotal");
        if (el) el.textContent = value.toFixed(2);
    },

    updateDiscount(value) {
        const el = document.getElementById("summary_discount");
        if (el) el.textContent = value.toFixed(2);
    },

    updateTotal(value) {
        const el = document.getElementById("summary_total");
        if (el) el.textContent = value.toFixed(2);
    },

    updatePaymentStatus({ remainingBalance, changeReturned, amountReceived }) {
        const container = document.getElementById("summary_payment_status");
        if (!container) return;

        let text = "Pendiente";
        let cls = "secondary";

        if (
            remainingBalance === 0 &&
            changeReturned === 0 &&
            amountReceived > 0
        ) {
            text = "Pagado exacto";
            cls = "success";
        } else if (remainingBalance === 0 && changeReturned > 0) {
            text = "Pagado con cambio";
            cls = "info";
        } else if (remainingBalance > 0 && amountReceived > 0) {
            text = "Pago parcial";
            cls = "warning";
        } else if (remainingBalance > 0) {
            text = "Pendiente";
            cls = "danger";
        }

        container.innerHTML = `<span class="badge bg-${cls}">${text}</span>`;
    },
    updatePaymentValues({ remainingBalance, changeReturned, amountReceived }) {
        const remainingEl = document.getElementById("summary_remaining");
        const changeEl = document.getElementById("summary_change");

        if (remainingEl)
            remainingEl.textContent = parseFloat(remainingBalance).toFixed(2);

        if (changeEl)
            changeEl.textContent = parseFloat(changeReturned).toFixed(2);
    },
};

window.saleSummary = saleSummary;
export default saleSummary;
