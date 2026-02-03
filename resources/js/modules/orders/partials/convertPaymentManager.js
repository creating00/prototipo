export default class ConvertPaymentManager {
    static PAYMENT_TYPES = { CASH: 1, CARD: 2, TRANSFER: 3, CHECK: 4 };

    // Contenedores de meta por payment type: CARD muestra bank, TRANSFER muestra account
    static META_MAP = {
        [ConvertPaymentManager.PAYMENT_TYPES.CARD]: "bank",
        [ConvertPaymentManager.PAYMENT_TYPES.TRANSFER]: "account",
    };

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
        this.bindPaymentType("single");
        this.bindPaymentType(1);
        this.bindPaymentType(2);

        this.bindSingleToDualSync();

        this.updateTotals();
    }

    getTotalOrder() {
        return (
            parseFloat(
                this.totalArsEl?.textContent
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

            // Toggle UI single vs dual
            document
                .getElementById("single_payment_section")
                ?.classList.toggle("d-none", this.isDual);
            document
                .getElementById("dual_payment_section")
                ?.classList.toggle("d-none", !this.isDual);
            const hiddenInput = document.getElementById("is_dual_payment");
            if (hiddenInput) hiddenInput.value = this.isDual ? "1" : "0";

            this.resetAmounts();
            this.updateTotals();
        });
    }

    resetAmounts() {
        const base = this.isUsd
            ? this.getTotalOrder() / this.exchangeRate
            : this.getTotalOrder();

        if (!this.isDual) {
            this.singleAmount?.setAttribute("value", this.format(base));
        } else {
            const half = this.format(base / 2);
            this.amount1?.setAttribute("value", half);
            this.amount2?.setAttribute("value", half);
        }
    }

    bindAmounts() {
        [this.singleAmount, this.amount1, this.amount2].forEach((el) =>
            this.addListener(el, "input", () => this.updateTotals()),
        );
    }

    bindExchangeRateListener() {
        const rateInput = document.getElementById("exchange_rate_blue");
        if (rateInput) rateInput.readOnly = this.isBranch;

        this.addListener(rateInput, "input", (e) => {
            this.exchangeRate = parseFloat(e.target.value) || this.exchangeRate;
            this.updateTotals();
        });
    }

    // Unifica bindSinglePaymentType y bindDualPaymentType
    // id: "single" | 1 | 2
    bindPaymentType(id) {
        const suffix = id === "single" ? "_single" : `_${id}`;
        const select = document.getElementById(
            id === "single" ? "convert_payment_type" : `payment_type_${id}`,
        );
        if (!select) return;

        const bank = document.getElementById(`container_bank_id${suffix}`);
        const account = document.getElementById(
            `container_bank_account_id${suffix}`,
        );

        this.addListener(select, "change", (e) =>
            this.togglePaymentMeta(e.target.value, bank, account),
        );
        this.togglePaymentMeta(select.value, bank, account); // Estado inicial
    }

    bindSingleToDualSync() {
        const singleType = document.getElementById("convert_payment_type");
        const singleAmount = document.getElementById("convert_amount_received");
        const singleBank = document.getElementById("bank_id_single");
        const singleAccount = document.getElementById("bank_account_id_single");

        // Destinos en Pago 1
        const p1Type = document.getElementById("payment_type_1");
        const p1Amount = document.getElementById("amount_received_1");
        const p1Bank = document.getElementById("bank_id_1");
        const p1Account = document.getElementById("bank_account_id_1");

        const sync = () => {
            if (this.isDual) return; // Si es dual, no sobreescribimos

            if (p1Type) p1Type.value = singleType.value;
            if (p1Amount) p1Amount.value = singleAmount.value;
            if (p1Bank) p1Bank.value = singleBank.value;
            if (p1Account) p1Account.value = singleAccount.value;

            // Disparar eventos change por si hay lógica de UI pendiente
            p1Type.dispatchEvent(new Event("change"));
        };

        [singleType, singleAmount, singleBank, singleAccount].forEach((el) => {
            if (el) this.addListener(el, "input", sync);
            if (el) this.addListener(el, "change", sync);
        });
    }

    togglePaymentMeta(paymentType, bankContainer, accountContainer) {
        if (!bankContainer || !accountContainer) return;

        const containers = { bank: bankContainer, account: accountContainer };
        const activeKey = ConvertPaymentManager.META_MAP[parseInt(paymentType)];

        // Oculta todos, muestra solo el activo (si existe)
        Object.entries(containers).forEach(([key, el]) =>
            el.classList.toggle("d-none", key !== activeKey),
        );
    }

    updateTotals() {
        const totalArs = this.getTotalOrder();
        const totalOrder = this.isUsd ? totalArs / this.exchangeRate : totalArs;

        const p1 =
            parseFloat(
                (this.isDual ? this.amount1 : this.singleAmount)?.value,
            ) || 0;
        const p2 = this.isDual ? parseFloat(this.amount2?.value) || 0 : 0;
        const totalPaid = p1 + p2;

        this.changeInput.value = this.format(
            Math.max(0, totalPaid - totalOrder),
        );
        this.remainingInput.value = this.format(
            Math.max(0, totalOrder - totalPaid),
        );

        this.totalPaidDisplay &&
            (this.totalPaidDisplay.textContent = this.format(totalPaid));
        this.p1Summary && (this.p1Summary.textContent = this.format(p1));
        this.p2Summary && (this.p2Summary.textContent = this.format(p2));

        this.syncHiddenInputs(totalArs); // Pasa totalArs para evitar recalcular
    }

    syncHiddenInputs(totalArs) {
        const exchangeSection = document.getElementById(
            "exchange_rate_section",
        );
        const usd = this.format(totalArs / this.exchangeRate);

        if (this.isUsd) {
            this.totalUsdHidden && (this.totalUsdHidden.value = usd);
            this.hiddenTotalArs && (this.hiddenTotalArs.value = "");
            this.usdTotalEl && (this.usdTotalEl.textContent = usd);
            this.usdWrapper?.classList.remove("d-none");
            exchangeSection?.classList.remove("d-none");
        } else {
            this.totalUsdHidden && (this.totalUsdHidden.value = "");
            this.hiddenTotalArs &&
                (this.hiddenTotalArs.value = this.format(totalArs));
            this.usdWrapper?.classList.add("d-none");
            exchangeSection?.classList.add("d-none");
        }
    }

    destroy() {
        this.handlers.forEach(({ el, event, handler }) =>
            el.removeEventListener(event, handler),
        );
        this.handlers = [];
    }
}
