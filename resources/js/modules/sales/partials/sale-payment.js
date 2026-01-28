// resources/js/modules/sales/partials/sale-payment.js
import PaymentCalculator from "../services/PaymentCalculator";
import FieldSyncer from "../services/FieldSyncer";
import RepairUiManager from "../services/RepairUiManager";
import { dispatchRepairCategoryChanged } from "@/helpers/repair-category-events";

const SALE_TYPE = { SALE: "1", REPAIR: "2" };

const salePayment = {
    saleTotal: 0,
    saleType: SALE_TYPE.SALE,
    isSyncing: false,

    // Cache de elementos para mejorar rendimiento
    _elementCache: new Map(),

    elements: {
        saleType: () => document.querySelector('select[name="sale_type"]'),
        repairType: () =>
            document.querySelector('select[name="repair_type_id"]') ||
            document.getElementById("repair_type"),
        repairAmount: () => document.getElementById("repair_amount"),
        hiddenRepairAmount: () =>
            document.getElementById("hidden_repair_amount"),
        amountReceived: () => document.getElementById("amount_received"),
        paymentTypeVisible: () =>
            document.querySelector('select[name="payment_type_visible"]'),
        totalAmount: () => document.getElementById("total_amount"),
        changeReturned: () => document.getElementById("change_returned"),
        remainingBalance: () => document.getElementById("remaining_balance"),
        statusIndicator: () =>
            document.getElementById("payment_status_indicator"),
        summaryStatus: () => document.getElementById("summary_payment_status"),
        discountInput: () => document.getElementById("discount_amount_input"),
        subtotalHidden: () => document.getElementById("subtotal_amount"),
        discountHidden: () => document.getElementById("hidden_discount_amount"),
        enableDualPayment: () => document.getElementById("enable_dual_payment"),
        modalAmount1: () => document.getElementById("amount_received_1_modal"),
        modalType1: () => document.getElementById("payment_type_1_modal"),
        modalAmount2: () => document.getElementById("amount_received_2_modal"),
        modalType2: () => document.getElementById("payment_type_2_modal"),
        labelTotalReceived: () =>
            document.getElementById("label_total_received"),
        labelChange: () => document.getElementById("label_change_returned"),
        labelRemaining: () =>
            document.getElementById("label_remaining_balance"),
        wrapperSingle: () => document.getElementById("wrapper_single_payment"),
        wrapperDualInfo: () =>
            document.getElementById("wrapper_dual_payment_info"),
        summaryAmount1: () => document.getElementById("summary_amount_1_label"),
        summaryAmount2: () => document.getElementById("summary_amount_2_label"),
        summaryType1: () =>
            document.getElementById("summary_payment_type_1_label"),
        summaryType2: () =>
            document.getElementById("summary_payment_type_2_label"),
    },

    fieldsToSync: {
        sale_date: "hidden_sale_date",
        amount_received_1_modal: "hidden_amount_received",
        amount_received_2_modal: "hidden_amount_received_2",
        payment_type_1_modal: "hidden_payment_type",
        payment_type_2_modal: "hidden_payment_type_2",
        change_returned: "hidden_change_returned",
        remaining_balance: "hidden_remaining_balance",
        repair_amount: "hidden_repair_amount",
        discount_id: "hidden_discount_id",
        payment_type_visible: "hidden_payment_type",
        discount_amount_input: "hidden_discount_amount",
    },

    init() {
        this.detectSaleType();
        this.bindEvents();
        FieldSyncer.sync(this.fieldsToSync);
        this.initialLoad();
    },

    detectSaleType() {
        const select = this.elements.saleType();
        if (select) {
            this.saleType = select.value;
            RepairUiManager.toggleFields(this.saleType === SALE_TYPE.REPAIR);
        }
    },

    initialLoad() {
        const subtotal = this.getFloatValue("subtotalHidden");
        const currentTotal = this.getFloatValue("totalAmount");

        this.saleTotal = currentTotal;

        this.dispatchEvent("sale:subtotalUpdated", { subtotal });

        const dualCheck = this.elements.enableDualPayment();
        if (dualCheck) {
            this.toggleDualPayment(dualCheck.checked);
        }

        this.syncRepairAmount();
        this.calculate();
    },

    bindEvents() {
        const el = this.elements;

        // Sincronización bidireccional entre Summary y Modal (Pago 1)
        this.bindBidirectionalSync(
            el.amountReceived(),
            el.modalAmount1(),
            "input",
        );

        this.bindBidirectionalSync(
            el.paymentTypeVisible(),
            el.modalType1(),
            "change",
            true,
        );

        // Toggle dual payment
        el.enableDualPayment()?.addEventListener("change", (e) => {
            this.toggleDualPayment(e.target.checked);
        });

        // Calcular al cambiar montos
        [el.amountReceived(), el.modalAmount1(), el.modalAmount2()]
            .filter(Boolean)
            .forEach((input) => {
                input.addEventListener("input", () => this.calculate());
            });

        // Cambios de tipo de venta y reparación
        el.saleType()?.addEventListener("change", (e) =>
            this.handleSaleTypeChange(e.target.value),
        );

        el.repairType()?.addEventListener("change", (e) =>
            this.handleRepairTypeChange(e.target.value),
        );

        // Descuento manual
        el.discountInput()?.addEventListener("input", () => {
            this.applyManualDiscount();
        });

        // Eventos personalizados
        document.addEventListener("sale:subtotalUpdated", () => {
            this.applyManualDiscount();
        });

        document.addEventListener("sale:totalUpdated", (e) => {
            this.setTotal(e.detail.total);
        });
    },

    // Método helper para vincular sincronización bidireccional
    bindBidirectionalSync(source, target, eventType, isSelect = false) {
        if (!source || !target) return;

        const syncMethod = isSelect ? this.syncSelects : this.syncFields;

        source.addEventListener(eventType, (e) => {
            syncMethod.call(this, e.target.value, target);
        });

        target.addEventListener(eventType, (e) => {
            syncMethod.call(this, e.target.value, source);
        });
    },

    syncFields(value, targetEl) {
        if (this.isSyncing || !targetEl || targetEl.value === value) return;

        this.isSyncing = true;
        targetEl.value = value;
        targetEl.dispatchEvent(new Event("input"));
        this.isSyncing = false;
    },

    syncSelects(value, targetEl) {
        if (this.isSyncing || !targetEl || targetEl.value === value) return;

        this.isSyncing = true;
        targetEl.value = value;

        if (targetEl._choices) {
            targetEl._choices.setChoiceByValue(value);
        }

        targetEl.dispatchEvent(new Event("change"));
        this.isSyncing = false;
    },

    syncRepairAmount() {
        const val = this.saleTotal.toFixed(2);
        const isRepair = this.saleType === SALE_TYPE.REPAIR;
        const targetValue = isRepair ? val : "";

        [this.elements.repairAmount(), this.elements.hiddenRepairAmount()]
            .filter(Boolean)
            .forEach((input) => {
                if (input.value !== targetValue) {
                    input.value = targetValue;
                    input.dispatchEvent(new Event("input"));
                }
            });
    },

    setTotal(value) {
        if (this.isSyncing) return;

        this.withSync(() => {
            this.saleTotal = parseFloat(value) || 0;
            this.syncRepairAmount();
            this.updateTotalsHiddenFields(this.saleTotal);
        });

        this.calculate();
        this.dispatchEvent("sale:totalUpdated", { total: this.saleTotal });
    },

    handleSaleTypeChange(value) {
        this.saleType = value;
        this.dispatchEvent("sale:typeChanged", { saleType: value });
        RepairUiManager.toggleFields(value === SALE_TYPE.REPAIR);
        this.refreshTotalFromSource();
    },

    applyManualDiscount() {
        if (this.isSyncing) return;

        const subtotal = this.getFloatValue("subtotalHidden");
        const discount = this.getFloatValue("discountInput");
        const newTotal = Math.max(0, subtotal - discount);

        this.dispatchEvent("sale:discountUpdated", { discount });

        if (this.saleTotal === newTotal && newTotal !== 0) {
            this.calculate();
            return;
        }

        this.saleTotal = newTotal;
        this.updateTotalDisplay(newTotal);
        this.syncRepairAmount();
        this.updateTotalsHiddenFields(this.saleTotal);
        this.calculate();

        this.withSync(() => {
            this.dispatchEvent("sale:totalUpdated", { total: newTotal });
        });
    },

    handleRepairTypeChange(typeId) {
        dispatchRepairCategoryChanged(typeId || null);
    },

    refreshTotalFromSource() {
        const source =
            this.saleType === SALE_TYPE.REPAIR
                ? this.elements.repairAmount()
                : this.elements.totalAmount();

        this.setTotal(source?.value || 0);
    },

    toggleDualPayment(enabled) {
        const el = this.elements;
        const hiddenDual = document.getElementById(
            "hidden_enable_dual_payment",
        );

        if (hiddenDual) hiddenDual.value = enabled ? "1" : "0";

        // Toggle visibilidad
        this.toggleElement(el.wrapperSingle(), !enabled);
        this.toggleElement(el.wrapperDualInfo(), enabled);

        // Configurar campos
        [el.modalAmount1(), el.modalAmount2()]
            .filter(Boolean)
            .forEach((input) => {
                input.readOnly = !enabled;
            });

        // Configurar selects
        [el.modalType1(), el.modalType2()].filter(Boolean).forEach((select) => {
            if (select._choices) {
                enabled ? select._choices.enable() : select._choices.disable();
            } else {
                select.disabled = !enabled;
            }
        });

        // Resetear segundo pago si se desactiva
        if (!enabled && el.modalAmount2()) {
            el.modalAmount2().value = "0.00";
            this.calculate();
        }
    },

    calculate() {
        if (this.isSyncing) return;

        this.withSync(() => {
            const r1 = this.getFloatValue("modalAmount1");
            const r2 = this.getFloatValue("modalAmount2");
            const totalReceived = r1 + r2;

            const result = PaymentCalculator.calculate(
                this.saleTotal,
                totalReceived,
            );
            const change = parseFloat(result.change) || 0;
            const balance = parseFloat(result.balance) || 0;

            this.updateCalculationLabels(
                totalReceived,
                r1,
                r2,
                change,
                balance,
            );
            this.updateHiddenFields(change, balance);
            this.updateStatusUI(totalReceived, change, balance);
        });
    },

    updateCalculationLabels(totalReceived, r1, r2, change, balance) {
        const el = this.elements;

        this.updateTextContent(
            el.labelTotalReceived(),
            totalReceived.toFixed(2),
        );
        this.updateTextContent(el.labelChange(), change.toFixed(2));
        this.updateTextContent(el.labelRemaining(), balance.toFixed(2));
        this.updateTextContent(el.summaryAmount1(), r1.toFixed(2));
        this.updateTextContent(el.summaryAmount2(), r2.toFixed(2));

        // Actualizar tipos de pago
        [1, 2].forEach((num) => {
            const label = el[`summaryType${num}`]();
            const select = el[`modalType${num}`]();

            if (label && select && select.selectedIndex !== -1) {
                label.textContent = select.options[select.selectedIndex].text;
            }
        });
    },

    updateHiddenFields(change, balance) {
        const hChange = document.getElementById("hidden_change_returned");
        const hBalance = document.getElementById("hidden_remaining_balance");

        if (hChange) hChange.value = change.toFixed(2);
        if (hBalance) hBalance.value = balance.toFixed(2);
    },

    updateStatusUI(received, change, balance) {
        const status = PaymentCalculator.getStatus(
            this.saleTotal,
            received,
            change,
            balance,
        );

        const badgeHtml = `<span class="badge bg-${status.class}">${status.label}</span>`;

        this.updateHTML(this.elements.statusIndicator(), badgeHtml);
        this.updateHTML(this.elements.summaryStatus(), badgeHtml);

        this.dispatchEvent("sale:paymentUpdated", {
            amountReceived: received,
            changeReturned: change,
            remainingBalance: balance,
            saleTotal: this.saleTotal,
            status,
        });
    },

    updateTotalsHiddenFields(total) {
        const totals = this.calculateTotalsByCurrency(total);
        const jsonString = JSON.stringify(totals);

        ["totals_source", "hidden_totals"].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.value = jsonString;
        });
    },

    calculateTotalsByCurrency(total) {
        const totals = {};
        const rows = document.querySelectorAll("#order-items-table tbody tr");

        if (rows.length > 0) {
            const currency =
                rows[0].querySelector('[name*="[currency]"]')?.value || "1";
            totals[currency] = parseFloat(total) || 0;
        } else {
            totals["1"] = parseFloat(total) || 0;
        }

        return totals;
    },

    updateTotalDisplay(total) {
        const totalDisplay = document.getElementById("total_amount_display");
        const totalHidden = document.getElementById("total_amount");

        if (totalDisplay) totalDisplay.textContent = total.toFixed(2);
        if (totalHidden) totalHidden.value = total.toFixed(2);
    },

    // ========== MÉTODOS HELPER ==========

    getFloatValue(elementKey) {
        const element = this.elements[elementKey]?.();
        return parseFloat(element?.value) || 0;
    },

    updateTextContent(element, value) {
        if (element) element.textContent = value;
    },

    updateHTML(element, html) {
        if (element) element.innerHTML = html;
    },

    toggleElement(element, show) {
        if (!element) return;
        show
            ? element.classList.remove("d-none")
            : element.classList.add("d-none");
    },

    withSync(callback) {
        this.isSyncing = true;
        try {
            callback();
        } finally {
            this.isSyncing = false;
        }
    },

    dispatchEvent(eventName, detail) {
        document.dispatchEvent(new CustomEvent(eventName, { detail }));
    },
};

window.salePayment = salePayment;
export default salePayment;
