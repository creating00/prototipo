// resources/js/modules/sales/payment/PaymentUIUpdater.js

export default class PaymentUIUpdater {
    constructor(domHelper) {
        this.dom = domHelper;
    }

    /**
     * Actualiza el display visual del total de la venta
     */
    updateTotalDisplay(total) {
        const formattedTotal = total.toFixed(2);

        // Actualiza el <span> $ 0.00
        this.dom.setText("total_amount_display", formattedTotal);

        // Actualiza el <input type="hidden">
        const totalHidden = this.dom.el("totalHidden", "total_amount");
        if (totalHidden) {
            totalHidden.value = formattedTotal;
        }
    }

    /**
     * Actualiza los campos ocultos de totales para el backend
     */
    updateTotalsHiddenFields(
        total,
        exchangeRate,
        isDollarMode,
        calculateCallback,
    ) {
        const rate = exchangeRate || 1;

        // Actualizar exchange rate hidden field
        const hRate = this.dom.el("hRate", "hidden_exchange_rate_blue");
        if (hRate) {
            hRate.value = rate;
        }

        const totalArs = parseFloat(total) || 0;

        // Persistencia de datos en JSON para el backend
        const totals = isDollarMode
            ? { 2: parseFloat((totalArs / rate).toFixed(2)) }
            : { 1: totalArs };

        const jsonString = JSON.stringify(totals);

        // Actualiza el equivalente USD estático
        this.dom.setText("summary_total_usd", (totalArs / rate).toFixed(2));

        ["totals_source", "hidden_totals"].forEach((id) => {
            const el = this.dom.el(id, id);
            if (el) el.value = jsonString;
        });

        // Trigger recalculo para convertir a la moneda seleccionada
        if (calculateCallback) {
            calculateCallback();
        }
    }

    /**
     * Actualiza el resumen visual de pagos (summary)
     */
    updateSummaryDisplay(data) {
        const {
            displayTotal,
            displayBalance,
            displayChange,
            displayR1,
            displayR2,
            totalReceivedArs,
            changeArs,
            balanceArs,
            isDualEnabled,
            symbol,
        } = data;

        // Resumen visual (en la moneda seleccionada)
        this.dom.setText("summary_total", displayTotal.toFixed(2));
        this.dom.setText("summary_remaining", displayBalance.toFixed(2));
        this.dom.setText("summary_change", displayChange.toFixed(2));

        // Labels técnicos (SIEMPRE en pesos - modal dual)
        this.dom.setText("label_total_received", totalReceivedArs.toFixed(2));
        this.dom.setText("label_change_returned", changeArs.toFixed(2));
        this.dom.setText("label_remaining_balance", balanceArs.toFixed(2));

        // Actualizar desglose de pago doble
        if (isDualEnabled) {
            this.dom.setText("summary_amount_1_label", displayR1.toFixed(2));
            this.dom.setText("summary_amount_2_label", displayR2.toFixed(2));
        }

        // Actualizar símbolos de moneda en summary
        document
            .querySelectorAll(".summary-symbol")
            .forEach((el) => (el.textContent = symbol));
    }

    /**
     * Actualiza los campos ocultos de change y balance
     */
    updateHiddenFields(displayChange, displayBalance) {
        const hChange = this.dom.el("hChange", "hidden_change_returned");
        const hBalance = this.dom.el("hBalance", "hidden_remaining_balance");

        if (hChange) hChange.value = displayChange.toFixed(2);
        if (hBalance) hBalance.value = displayBalance.toFixed(2);
    }

    /**
     * Actualiza los badges de estado de pago
     */
    updatePaymentStatusBadges(badgeHtml) {
        this.dom.setHTML("payment_status_indicator", badgeHtml);
        this.dom.setHTML("summary_payment_status", badgeHtml);
    }
}
