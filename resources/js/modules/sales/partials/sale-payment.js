// resources/js/modules/sales/partials/sale-payment.js
import PaymentCalculator from "../services/PaymentCalculator";
import FieldSyncer from "../services/FieldSyncer";
import RepairUiManager from "../services/RepairUiManager";
import { dispatchRepairCategoryChanged } from "@/helpers/repair-category-events";

const SALE_TYPE = Object.freeze({ SALE: "1", REPAIR: "2" });
const PAYMENT_TYPE = Object.freeze({ CASH: "1", CARD: "2", TRANSFER: "3" });
const PAYMENT_MODELS = Object.freeze({
    [PAYMENT_TYPE.CARD]: "App\\Models\\Bank",
    [PAYMENT_TYPE.TRANSFER]: "App\\Models\\BankAccount",
});

class SalePayment {
    constructor() {
        this.saleTotal = 0;
        this.saleType = SALE_TYPE.SALE;
        this.isSyncing = false;
        this._domCache = new Map();
        this.fieldsToSync = {
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
            bank_id_visible: "hidden_payment_method_id",
            bank_account_id_visible: "hidden_payment_method_id",
            bank_id_1_modal: "hidden_payment_method_id",
            bank_account_id_1_modal: "hidden_payment_method_id",
            bank_id_2_modal: "hidden_payment_method_id_2",
            bank_account_id_2_modal: "hidden_payment_method_id_2",
            exchange_rate_blue: "hidden_exchange_rate_blue",
        };
    }

    // Cached DOM getter
    el(id, selector) {
        if (!this._domCache.has(id)) {
            this._domCache.set(
                id,
                typeof selector === "string"
                    ? document.getElementById(selector)
                    : document.querySelector(selector),
            );
        }
        return this._domCache.get(id);
    }

    init() {
        this.detectSaleType();
        this.bindEvents();
        FieldSyncer.sync(this.fieldsToSync);
        this.initialLoad();
    }

    detectSaleType() {
        // Cambiamos el selector genérico por el ID específico 'sale_type'
        const select = this.el("saleType", "sale_type");
        if (select) {
            this.saleType = select.value;
            // Ejecución inmediata para el estado inicial
            RepairUiManager.toggleFields(this.saleType === SALE_TYPE.REPAIR);
        }
    }

    initialLoad() {
        const subtotal = this.getFloat("subtotal_amount");
        const currentTotal = this.getFloat("total_amount");
        this.saleTotal = currentTotal;

        this.dispatchEvent("sale:subtotalUpdated", { subtotal });

        const paymentType = this.el(
            "payType",
            'select[name="payment_type_visible"]',
        );
        if (paymentType) {
            this.updateMethodVisibility(paymentType.value, "");
            this.updateMethodVisibility(paymentType.value, "1");
        }

        const modalType2 = this.el("modalType2", "payment_type_2_modal");
        if (modalType2) this.updateMethodVisibility(modalType2.value, "2");

        const dualCheck = this.el("dualCheck", "enable_dual_payment");
        if (dualCheck) this.toggleDualPayment(dualCheck.checked);

        this.updateTotalsHiddenFields(this.saleTotal);
        this.syncRepairAmount();
        this.calculate();
    }

    bindEvents() {
        // Bidirectional syncs
        this.bindSync(
            this.el("amtRcv", "amount_received"),
            this.el("modal1", "amount_received_1_modal"),
            "input",
        );
        this.bindSync(
            this.el("payTypeVis", 'select[name="payment_type_visible"]'),
            this.el("modalType1", "payment_type_1_modal"),
            "change",
            true,
        );
        this.bindSync(
            this.el("bankVis", 'select[name="bank_id_visible"]'),
            this.el("modalBank1", "bank_id_1_modal"),
            "change",
            true,
        );
        this.bindSync(
            this.el("accVis", 'select[name="bank_account_id_visible"]'),
            this.el("modalAcc1", "bank_account_id_1_modal"),
            "change",
            true,
        );

        // Event listeners
        this.on("dualCheck", "enable_dual_payment", "change", (e) =>
            this.toggleDualPayment(e.target.checked),
        );

        [
            "amount_received",
            "amount_received_1_modal",
            "amount_received_2_modal",
            "exchange_rate_blue",
        ].forEach((id) =>
            this.on(id, id, "input", () => {
                this.calculate();
                this.updateTotalsHiddenFields(this.saleTotal);
            }),
        );

        this.on("saleType", "sale_type", "change", (e) =>
            this.handleSaleTypeChange(e.target.value),
        );

        this.on("repairType", 'select[name="repair_type_id"]', "change", (e) =>
            this.handleRepairTypeChange(e.target.value),
        );
        this.on("discount", "discount_amount_input", "input", () =>
            this.applyManualDiscount(),
        );
        this.on("payDollars", "pay_in_dollars", "change", () =>
            this.updateTotalsHiddenFields(this.saleTotal),
        );

        this.on(
            "payTypeVis",
            'select[name="payment_type_visible"]',
            "change",
            (e) => {
                this.updateMethodVisibility(e.target.value, "");
                this.updateMethodVisibility(e.target.value, "1");
            },
        );
        this.on("modalType2", "payment_type_2_modal", "change", (e) =>
            this.updateMethodVisibility(e.target.value, "2"),
        );

        this.on("modalType1", "payment_type_1_modal", "change", () => {
            const isDualEnabled = this.el(
                "dualCheck",
                "enable_dual_payment",
            )?.checked;
            if (isDualEnabled) {
                this.updatePaymentTypeLabels();
            }
        });

        this.on("modalType2", "payment_type_2_modal", "change", () => {
            const isDualEnabled = this.el(
                "dualCheck",
                "enable_dual_payment",
            )?.checked;
            if (isDualEnabled) {
                this.updatePaymentTypeLabels();
            }
        });

        document.addEventListener("sale:subtotalUpdated", () =>
            this.applyManualDiscount(),
        );
        document.addEventListener("sale:totalUpdated", (e) =>
            this.setTotal(e.detail.total),
        );
    }

    on(cacheKey, id, event, handler) {
        const el = this.el(cacheKey, id);
        el?.addEventListener(event, handler);
    }

    bindSync(source, target, eventType, isSelect = false) {
        if (!source || !target) return;
        const syncFn = isSelect ? this.syncSelects : this.syncFields;
        source.addEventListener(eventType, (e) =>
            syncFn.call(this, e.target.value, target),
        );
        target.addEventListener(eventType, (e) =>
            syncFn.call(this, e.target.value, source),
        );
    }

    syncFields(value, targetEl) {
        if (this.isSyncing || !targetEl || targetEl.value === value) return;
        this.withSync(() => {
            targetEl.value = value;
            targetEl.dispatchEvent(new Event("input"));
        });
    }

    syncSelects(value, targetEl) {
        if (this.isSyncing || !targetEl || targetEl.value === value) return;
        this.withSync(() => {
            targetEl.value = value;
            if (targetEl._choices) targetEl._choices.setChoiceByValue(value);
            targetEl.dispatchEvent(new Event("change"));
        });
    }

    syncRepairAmount() {
        const val = this.saleTotal.toFixed(2);
        const targetValue = this.saleType === SALE_TYPE.REPAIR ? val : "";
        [
            this.el("repairAmt", "repair_amount"),
            this.el("hiddenRepair", "hidden_repair_amount"),
        ]
            .filter(Boolean)
            .forEach((input) => {
                if (input.value !== targetValue) {
                    input.value = targetValue;
                    input.dispatchEvent(new Event("input"));
                }
            });
    }

    setTotal(value) {
        if (this.isSyncing) return;
        this.withSync(() => {
            this.saleTotal = parseFloat(value) || 0;
            this.syncRepairAmount();
            this.updateTotalsHiddenFields(this.saleTotal);
        });
        this.calculate();
        this.dispatchEvent("sale:totalUpdated", { total: this.saleTotal });
    }

    handleSaleTypeChange(value) {
        this.saleType = value;
        this.dispatchEvent("sale:typeChanged", { saleType: value });
        RepairUiManager.toggleFields(value === SALE_TYPE.REPAIR);
        this.refreshTotalFromSource();
    }

    applyManualDiscount() {
        if (this.isSyncing) return;

        const subtotal = this.getFloat("subtotal_amount");
        const discount = this.getFloat("discount_amount_input");

        const newTotal = Math.max(0, subtotal - discount);
        this.saleTotal = newTotal;

        // Total del pedido (siempre en $)
        this.updateTotalDisplay(newTotal);

        this.syncRepairAmount();
        this.updateTotalsHiddenFields(newTotal);

        // recalculamos UNA sola vez
        this.calculate();

        this.dispatchEvent("sale:discountUpdated", { discount });
    }

    handleRepairTypeChange(typeId) {
        dispatchRepairCategoryChanged(typeId || null);
    }

    refreshTotalFromSource() {
        const source =
            this.saleType === SALE_TYPE.REPAIR
                ? this.el("repairAmt", "repair_amount")
                : this.el("totalAmt", "total_amount");
        this.setTotal(source?.value || 0);
    }

    toggleDualPayment(enabled) {
        const hiddenDual = this.el("hiddenDual", "hidden_enable_dual_payment");
        if (hiddenDual) hiddenDual.value = enabled ? "1" : "0";

        this.toggle(this.el("wrapSingle", "wrapper_single_payment"), !enabled);
        this.toggle(this.el("wrapDual", "wrapper_dual_payment_info"), enabled);

        [
            this.el("modal1", "amount_received_1_modal"),
            this.el("modal2", "amount_received_2_modal"),
        ]
            .filter(Boolean)
            .forEach((input) => (input.readOnly = !enabled));

        [
            "modalType1",
            "modalType2",
            "modalBank1",
            "modalBank2",
            "modalAcc1",
            "modalAcc2",
        ]
            .map((key) =>
                this.el(
                    key,
                    key
                        .replace("modal", "")
                        .replace(/(\d)/, "_$1_modal")
                        .toLowerCase(),
                ),
            )
            .filter(Boolean)
            .forEach((select) => {
                if (select._choices) {
                    enabled
                        ? select._choices.enable()
                        : select._choices.disable();
                } else {
                    select.disabled = !enabled;
                }
            });

        if (!enabled && this.el("modal2", "amount_received_2_modal")) {
            this.el("modal2", "amount_received_2_modal").value = "0.00";
            this.calculate();
        }

        if (enabled) {
            this.updatePaymentTypeLabels();
        }
    }

    calculate() {
        if (this.isSyncing) return;
        this.withSync(() => {
            const isDollarMode = this.el(
                "payDollars",
                "pay_in_dollars",
            )?.checked;
            const rate = this.getFloat("exchange_rate_blue") || 1;
            const isDualEnabled = this.el(
                "dualCheck",
                "enable_dual_payment",
            )?.checked;

            // 1. Obtener montos
            let r1 = this.getFloat("amount_received_1_modal");
            let r2 = this.getFloat("amount_received_2_modal");

            // Solo convertir a pesos si NO es modo dual
            // En modo dual, los valores modales YA están en pesos
            if (isDollarMode && !isDualEnabled) {
                r1 = r1 * rate;
            }

            const totalReceivedArs = r1 + r2;

            // 2. El calculador siempre opera en Pesos (Moneda Base)
            const result = PaymentCalculator.calculate(
                this.saleTotal,
                totalReceivedArs,
            );

            const changeArs = parseFloat(result.change) || 0;
            const balanceArs = parseFloat(result.balance) || 0;

            const symbol = isDollarMode ? "U$D" : "$";

            // 3. Conversión para visualización y campos ocultos
            const displayTotal = isDollarMode
                ? this.saleTotal / rate
                : this.saleTotal;
            const displayBalance = isDollarMode
                ? balanceArs / rate
                : balanceArs;
            const displayChange = isDollarMode ? changeArs / rate : changeArs;

            // Conversión de montos recibidos para el summary
            const displayR1 = isDollarMode ? r1 / rate : r1;
            const displayR2 = isDollarMode ? r2 / rate : r2;

            // Resumen visual (en la moneda seleccionada)
            this.setText("summary_total", displayTotal.toFixed(2));
            this.setText("summary_remaining", displayBalance.toFixed(2));
            this.setText("summary_change", displayChange.toFixed(2));

            // Labels técnicos (SIEMPRE en pesos - estos son los que están en el modal dual)
            this.setText("label_total_received", totalReceivedArs.toFixed(2));
            this.setText("label_change_returned", changeArs.toFixed(2));
            this.setText("label_remaining_balance", balanceArs.toFixed(2));

            // Actualizar desglose de pago doble (en la moneda seleccionada)
            if (isDualEnabled) {
                this.setText("summary_amount_1_label", displayR1.toFixed(2));
                this.setText("summary_amount_2_label", displayR2.toFixed(2));

                // Actualizar etiquetas de tipo de pago
                this.updatePaymentTypeLabels();
            }

            // Actualizar símbolos del summary (no de los labels)
            document
                .querySelectorAll(".summary-symbol")
                .forEach((el) => (el.textContent = symbol));

            // 4. Sincronizar campos ocultos para el backend
            const hChange = this.el("hChange", "hidden_change_returned");
            const hBalance = this.el("hBalance", "hidden_remaining_balance");

            if (hChange) hChange.value = displayChange.toFixed(2);
            if (hBalance) hBalance.value = displayBalance.toFixed(2);

            // Badges (Usan valores en pesos para determinar el estado)
            const status = PaymentCalculator.getStatus(
                this.saleTotal,
                totalReceivedArs,
                changeArs,
                balanceArs,
            );
            const badgeHtml = `<span class="badge bg-${status.class}">${status.label}</span>`;
            this.setHTML("payment_status_indicator", badgeHtml);
            this.setHTML("summary_payment_status", badgeHtml);
        });
    }

    updatePaymentTypeLabels() {
        const paymentType1 = this.getPaymentTypeLabel("payment_type_1_modal");
        const paymentType2 = this.getPaymentTypeLabel("payment_type_2_modal");

        this.setText("summary_payment_type_1_label", paymentType1);
        this.setText("summary_payment_type_2_label", paymentType2);
    }

    getPaymentTypeLabel(selectId) {
        const select = this.el(selectId, selectId);
        if (!select) return "Método";

        try {
            // Obtener el texto de la opción seleccionada directamente del DOM
            const selectedOption = select.querySelector("option:checked");
            if (selectedOption) {
                return selectedOption.textContent.trim();
            }

            // Fallback: usar selectedIndex
            if (
                select.selectedIndex >= 0 &&
                select.options[select.selectedIndex]
            ) {
                return select.options[select.selectedIndex].textContent.trim();
            }

            return "Método";
        } catch (error) {
            console.warn(`Error obteniendo label de ${selectId}:`, error);
            return "Método";
        }
    }

    updateTotalsHiddenFields(total) {
        const rate = this.getFloat("exchange_rate_blue") || 1;
        const hRate = this.el("hRate", "hidden_exchange_rate_blue");
        if (hRate) {
            hRate.value = rate;
        }
        const totalArs = parseFloat(total) || 0;
        const isDollarMode = this.el("payDollars", "pay_in_dollars")?.checked;

        // Persistencia de datos en JSON para el backend
        const totals = isDollarMode
            ? { 2: parseFloat((totalArs / rate).toFixed(2)) }
            : { 1: totalArs };

        const jsonString = JSON.stringify(totals);

        // Actualiza el equivalente USD estático
        this.setText("summary_total_usd", (totalArs / rate).toFixed(2));

        ["totals_source", "hidden_totals"].forEach((id) => {
            const el = this.el(id, id);
            if (el) el.value = jsonString;
        });

        // convirtiéndolos a la moneda seleccionada.
        this.calculate();
    }

    updateMethodVisibility(paymentType, suffix = "") {
        const isCard = paymentType === PAYMENT_TYPE.CARD;
        const isTransfer = paymentType === PAYMENT_TYPE.TRANSFER;

        if (suffix === "") {
            this.toggle(
                this.el("contBank", "container_payment_method_bank"),
                isCard,
            );
            this.toggle(
                this.el("contAcc", "container_payment_method_account"),
                isTransfer,
            );
            this.toggle(
                this.el("contBank1", "container_bank_id_1_modal"),
                isCard,
            );
            this.toggle(
                this.el("contAcc1", "container_bank_account_id_1_modal"),
                isTransfer,
            );
        } else {
            this.toggle(
                this.el(
                    `contBank${suffix}`,
                    `container_bank_id_${suffix}_modal`,
                ),
                isCard,
            );
            this.toggle(
                this.el(
                    `contAcc${suffix}`,
                    `container_bank_account_id_${suffix}_modal`,
                ),
                isTransfer,
            );
        }

        const bankSelect =
            suffix === ""
                ? this.el("bankVis", 'select[name="bank_id_visible"]')
                : this.el(`bank${suffix}`, `bank_id_${suffix}_modal`);
        const accountSelect =
            suffix === ""
                ? this.el("accVis", 'select[name="bank_account_id_visible"]')
                : this.el(`acc${suffix}`, `bank_account_id_${suffix}_modal`);

        if (!isCard) this.resetSelect(bankSelect);
        if (!isTransfer) this.resetSelect(accountSelect);

        if (!isCard && !isTransfer) {
            const idField = this.el(
                `hiddenMethod${suffix}`,
                `hidden_payment_method_id${suffix === "2" ? "_2" : ""}`,
            );
            if (idField) idField.value = "";
        }

        this.updatePolymorphicType(paymentType, suffix);
    }

    resetSelect(selectEl) {
        if (!selectEl) return;
        this.withSync(() => {
            selectEl.value = "";
            if (selectEl._choices) selectEl._choices.setChoiceByValue("");
            selectEl.dispatchEvent(new Event("change"));
        });
    }

    updatePolymorphicType(paymentType, suffix = "") {
        const hiddenType = this.el(
            `hiddenType${suffix}`,
            `hidden_payment_method_type${suffix === "2" ? "_2" : ""}`,
        );
        if (hiddenType) hiddenType.value = PAYMENT_MODELS[paymentType] || "";
    }

    updateTotalDisplay(total) {
        const formattedTotal = total.toFixed(2);

        // Actualiza el <span> $ 0.00
        this.setText("total_amount_display", formattedTotal);

        // Actualiza el <input type="hidden">
        const totalHidden = this.el("totalHidden", "total_amount");
        if (totalHidden) {
            totalHidden.value = formattedTotal;
        }
    }

    // Utilities
    getFloat(id) {
        const el = this.el(id, id);
        return parseFloat(el?.value) || 0;
    }

    setText(id, value) {
        const el = this.el(id, id);
        if (el) el.textContent = value;
    }

    setHTML(id, html) {
        const el = this.el(id, id);
        if (el) el.innerHTML = html;
    }

    toggle(element, show) {
        if (!element) return;
        element.classList.toggle("d-none", !show);
    }

    withSync(callback) {
        this.isSyncing = true;
        try {
            callback();
        } finally {
            this.isSyncing = false;
        }
    }

    dispatchEvent(eventName, detail) {
        document.dispatchEvent(new CustomEvent(eventName, { detail }));
    }
}

const salePayment = new SalePayment();
window.salePayment = salePayment;
export default salePayment;
