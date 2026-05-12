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

    // En saleSummary.js
    updatePaymentStatus(detail) {
        const container = document.getElementById("summary_payment_status");
        if (!container || !detail.status) return;

        // Usar el objeto status que ya viene procesado desde salePayment
        const { label, class: cls } = detail.status;
        container.innerHTML = `<span class="badge bg-${cls}">${label}</span>`;
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
