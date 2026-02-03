export default class ConvertPaymentManager {
    constructor({ exchangeRate = 1000, isBranch = false }) {
        this.exchangeRate = exchangeRate;
        this.isBranch = isBranch;
        this.isDual = false;
        this.isUsd = false;
        this.handlers = [];

        // Cache DOM
        this.totalArsEl = document.getElementById("display_total_ars");
        this.usdWrapper = document.getElementById("wrapper_usd_total");
        this.usdTotalEl = document.getElementById("summary_total_usd");
        this.totalUsdHidden = document.getElementById(
            "total_amount_usd_hidden",
        );
        this.hiddenTotalArs = document.getElementById("total_amount");

        this.singleAmount = document.getElementById("convert_amount_received");
        this.amount1 = document.getElementById("amount_received_1");
        this.amount2 = document.getElementById("amount_received_2");

        this.changeInput = document.getElementById("convert_change_returned");
        this.remainingInput = document.getElementById(
            "convert_remaining_balance",
        );

        this.totalPaidDisplay = document.getElementById("total_paid_display");
        this.p1Summary = document.getElementById("payment_1_summary");
        this.p2Summary = document.getElementById("payment_2_summary");

        this.init();
    }

    init() {
        this.bindToggles();
        this.bindAmounts();
        this.bindExchangeRateListener();
        this.updateTotals();
    }

    getTotalOrder() {
        return (
            parseFloat(
                this.totalArsEl.textContent
                    .replace(/\./g, "")
                    .replace(",", "."),
            ) || 0
        );
    }

    format(n) {
        return Number(n || 0).toFixed(2);
    }

    addListener(el, event, handler) {
        if (!el) return;
        el.addEventListener(event, handler);
        this.handlers.push({ el, event, handler });
    }

    bindToggles() {
        const usdToggle = document.getElementById("pay_in_dollars");
        const dualToggle = document.getElementById("enable_dual_payment");

        if (usdToggle) this.isUsd = usdToggle.checked;

        this.addListener(usdToggle, "change", (e) => {
            this.isUsd = e.target.checked;
            this.resetAmounts();
            this.updateTotals();
        });

        this.addListener(dualToggle, "change", (e) => {
            this.isDual = e.target.checked;
            this.toggleDualUI();
            this.resetAmounts();
            this.updateTotals();
        });
    }

    // Sincroniza valores iniciales al cambiar modo (USD/Dual)
    resetAmounts() {
        const total = this.getTotalOrder();
        const base = this.isUsd ? total / this.exchangeRate : total;

        if (!this.isDual) {
            if (this.singleAmount) this.singleAmount.value = this.format(base);
        } else {
            const half = base / 2;
            if (this.amount1) this.amount1.value = this.format(half);
            if (this.amount2) this.amount2.value = this.format(half);
        }
    }

    bindAmounts() {
        this.addListener(this.singleAmount, "input", () => this.updateTotals());
        this.addListener(this.amount1, "input", () => this.updateTotals());
        this.addListener(this.amount2, "input", () => this.updateTotals());
    }

    bindExchangeRateListener() {
        const rateInput = document.getElementById("exchange_rate_blue");
        if (rateInput) {
            // Ahora usamos this.isBranch
            rateInput.readOnly = this.isBranch;
        }

        this.addListener(rateInput, "input", () => {
            this.exchangeRate =
                parseFloat(rateInput.value) || this.exchangeRate;
            this.updateTotals();
        });
    }

    toggleDualUI() {
        const isDual = this.isDual;
        document
            .getElementById("single_payment_section")
            ?.classList.toggle("d-none", isDual);
        document
            .getElementById("dual_payment_section")
            ?.classList.toggle("d-none", !isDual);
        const hiddenInput = document.getElementById("is_dual_payment");
        if (hiddenInput) hiddenInput.value = isDual ? "1" : "0";
    }

    // Procesa cálculos de vuelto y remanente
    updateTotals() {
        const totalOrder = this.isUsd
            ? this.getTotalOrder() / this.exchangeRate
            : this.getTotalOrder();

        let p1 = 0;
        let p2 = 0;
        let totalPaid = 0;

        if (this.isDual) {
            p1 = parseFloat(this.amount1.value) || 0;
            p2 = parseFloat(this.amount2.value) || 0;
            totalPaid = p1 + p2;
        } else {
            p1 = parseFloat(this.singleAmount.value) || 0;
            totalPaid = p1;
        }

        // Actualizar Inputs de resultado
        this.changeInput.value = this.format(
            Math.max(0, totalPaid - totalOrder),
        );
        this.remainingInput.value = this.format(
            Math.max(0, totalOrder - totalPaid),
        );

        if (this.totalPaidDisplay)
            this.totalPaidDisplay.textContent = this.format(totalPaid);
        if (this.p1Summary) this.p1Summary.textContent = this.format(p1);
        if (this.p2Summary) this.p2Summary.textContent = this.format(p2);

        this.syncHiddenInputs();
    }

    // Sincroniza inputs ocultos para el backend y visibilidad UI
    syncHiddenInputs() {
        const exchangeSection = document.getElementById(
            "exchange_rate_section",
        );
        const totalArs = this.getTotalOrder();
        const usd = totalArs / this.exchangeRate;
        const formattedUsd = this.format(usd);

        if (!this.isUsd) {
            if (this.totalUsdHidden) this.totalUsdHidden.value = "";
            if (this.hiddenTotalArs)
                this.hiddenTotalArs.value = this.format(totalArs);
            this.usdWrapper?.classList.add("d-none");
            exchangeSection?.classList.add("d-none");
        } else {
            if (this.totalUsdHidden) this.totalUsdHidden.value = formattedUsd;
            if (this.hiddenTotalArs) this.hiddenTotalArs.value = "";
            if (this.usdTotalEl) this.usdTotalEl.textContent = formattedUsd;
            this.usdWrapper?.classList.remove("d-none");
            exchangeSection?.classList.remove("d-none");
        }
    }

    destroy() {
        this.handlers.forEach(({ el, event, handler }) =>
            el.removeEventListener(event, handler),
        );
        this.handlers = [];
    }
}
