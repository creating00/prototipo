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

    elements: {
        // --- VISTA PRINCIPAL (Summary / Globals) ---
        saleType: () => document.querySelector('select[name="sale_type"]'),
        repairType: () =>
            document.querySelector('select[name="repair_type_id"]') ||
            document.getElementById("repair_type"),
        repairAmount: () => document.getElementById("repair_amount"),

        // El input de monto en el resumen (Pago Único)
        amountReceived: () => document.getElementById("amount_received"),
        paymentTypeVisible: () =>
            document.querySelector('select[name="payment_type_visible"]'),

        totalAmount: () => document.getElementById("total_amount"),
        changeReturned: () => document.getElementById("change_returned"),
        remainingBalance: () => document.getElementById("remaining_balance"),

        // Indicadores de estado
        statusIndicator: () =>
            document.getElementById("payment_status_indicator"),
        summaryStatus: () => document.getElementById("summary_payment_status"),

        // Totales y descuentos
        discountInput: () => document.getElementById("discount_amount_input"),
        subtotalHidden: () => document.getElementById("subtotal_amount"),
        discountHidden: () => document.getElementById("hidden_discount_amount"),

        // --- MODAL DE PAGO (Doble Pago) ---
        enableDualPayment: () => document.getElementById("enable_dual_payment"),

        // Bloque Pago 1 (Espejo del Summary)
        modalAmount1: () => document.getElementById("amount_received_1_modal"),
        modalType1: () => document.getElementById("payment_type_1_modal"),

        // Bloque Pago 2
        modalAmount2: () => document.getElementById("amount_received_2_modal"),
        modalType2: () => document.getElementById("payment_type_2_modal"),

        // Etiquetas de cálculo en el Modal
        labelTotalReceived: () =>
            document.getElementById("label_total_received"),
        labelChange: () => document.getElementById("label_change_returned"),
        labelRemaining: () =>
            document.getElementById("label_remaining_balance"),

        // Dentro de elements:
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
        amount_received_1_modal: "hidden_amount_received", // Modal P1 -> Hidden Principal
        amount_received_2_modal: "hidden_amount_received_2", // Modal P2 -> Hidden P2
        payment_type_1_modal: "hidden_payment_type",
        payment_type_2_modal: "hidden_payment_type_2",
        change_returned: "hidden_change_returned",
        remaining_balance: "hidden_remaining_balance",
        repair_amount: "hidden_repair_amount",
        discount_id: "hidden_discount_id",
        payment_type_visible: "hidden_payment_type",
        discount_amount_input: "hidden_discount_amount",
    },

    init: function () {
        this.detectSaleType();
        this.bindEvents();
        FieldSyncer.sync(this.fieldsToSync);
        this.initialLoad();
    },

    detectSaleType: function () {
        const select = this.elements.saleType();
        if (select) {
            this.saleType = select.value;
            RepairUiManager.toggleFields(this.saleType === SALE_TYPE.REPAIR);
        }
    },

    initialLoad: function () {
        const el = this.elements;
        const subtotal = parseFloat(el.subtotalHidden()?.value) || 0;

        // CAPTURAR TOTAL ACTUAL (Evita que se resetee a -1 o 0 si ya hay datos)
        const currentTotal =
            parseFloat(document.getElementById("total_amount")?.value) || 0;
        this.saleTotal = currentTotal;

        document.dispatchEvent(
            new CustomEvent("sale:subtotalUpdated", {
                detail: { subtotal: subtotal },
            }),
        );

        // Detectar si ya es dual para activar la UI correctamente
        const dualCheck = el.enableDualPayment();
        if (dualCheck) {
            this.toggleDualPayment(dualCheck.checked);
        }

        // Ejecutar cálculo inicial para llenar las etiquetas (labels) del modal y summary
        this.calculate();
    },

    bindEvents: function () {
        const el = this.elements;

        // 1. Sincronización Espejo: Summary <-> Modal (Pago 1)
        // Montos
        el.amountReceived()?.addEventListener("input", (e) => {
            this.syncFields(e.target.value, el.modalAmount1());
        });

        el.modalAmount1()?.addEventListener("input", (e) => {
            this.syncFields(e.target.value, el.amountReceived());
        });

        // Tipos de Pago (Selects)
        el.paymentTypeVisible()?.addEventListener("change", (e) => {
            this.syncSelects(e.target.value, el.modalType1());
        });

        el.modalType1()?.addEventListener("change", (e) => {
            this.syncSelects(e.target.value, el.paymentTypeVisible());
        });

        // 2. Control de Doble Pago (Habilitar campos)
        el.enableDualPayment()?.addEventListener("change", (e) => {
            this.toggleDualPayment(e.target.checked);
        });

        // 3. Cálculos de Pago (Monto 1 y Monto 2)
        // Agrupamos listeners para evitar redundancia
        [el.amountReceived(), el.modalAmount2(), el.modalAmount1()].forEach(
            (input) => {
                input?.addEventListener("input", () => this.calculate());
            },
        );

        // 4. Gestión de Venta y Reparación
        el.saleType()?.addEventListener("change", (e) =>
            this.handleSaleTypeChange(e.target.value),
        );
        el.repairType()?.addEventListener("change", (e) =>
            this.handleRepairTypeChange(e.target.value),
        );

        // 5. Gestión de Descuentos
        el.discountInput()?.addEventListener("input", () => {
            this.applyManualDiscount();
        });

        // 6. Eventos Globales
        document.addEventListener("sale:subtotalUpdated", () => {
            this.applyManualDiscount();
        });

        document.addEventListener("sale:totalUpdated", (e) => {
            this.setTotal(e.detail.total);
        });
    },

    syncFields: function (value, targetEl) {
        if (this.isSyncing) return;
        if (targetEl && targetEl.value !== value) {
            this.isSyncing = true;
            targetEl.value = value;
            targetEl.dispatchEvent(new Event("input"));
            this.isSyncing = false;
        }
    },

    syncSelects: function (value, targetEl) {
        if (this.isSyncing || !targetEl || targetEl.value === value) return;

        this.isSyncing = true;
        targetEl.value = value;

        // Soporte para Choices.js si está presente
        if (targetEl._choices) {
            targetEl._choices.setChoiceByValue(value);
        }

        targetEl.dispatchEvent(new Event("change"));
        this.isSyncing = false;
    },

    setTotal: function (value) {
        if (this.isSyncing) return;
        this.isSyncing = true;

        this.saleTotal = parseFloat(value) || 0;
        const repairInput = this.elements.repairAmount();

        if (this.saleType === SALE_TYPE.REPAIR && repairInput) {
            const currentVal = parseFloat(repairInput.value) || 0;
            if (currentVal !== this.saleTotal) {
                repairInput.value = this.saleTotal.toFixed(2);
                repairInput.dispatchEvent(new Event("input"));
            }
        }

        this.updateTotalsHiddenFields(this.saleTotal);
        this.isSyncing = false; // Liberamos antes de calculate para permitir actualizaciones de UI
        this.calculate();

        document.dispatchEvent(
            new CustomEvent("sale:totalUpdated", {
                detail: { total: this.saleTotal },
            }),
        );
    },

    handleSaleTypeChange: function (value) {
        this.saleType = value;
        document.dispatchEvent(
            new CustomEvent("sale:typeChanged", {
                detail: { saleType: value },
            }),
        );
        RepairUiManager.toggleFields(value === SALE_TYPE.REPAIR);
        this.refreshTotalFromSource();
    },

    applyManualDiscount: function () {
        if (this.isSyncing) return;

        const subtotalEl = this.elements.subtotalHidden();
        const discountEl = this.elements.discountInput();
        if (!subtotalEl) return;

        const subtotal = parseFloat(subtotalEl.value) || 0;
        const discount = parseFloat(discountEl?.value) || 0;
        const newTotal = Math.max(0, subtotal - discount);

        document.dispatchEvent(
            new CustomEvent("sale:discountUpdated", {
                detail: { discount: discount },
            }),
        );

        if (this.saleTotal === newTotal && newTotal !== 0) {
            this.calculate();
            return;
        }

        this.saleTotal = newTotal;

        const totalDisplay = document.getElementById("total_amount_display");
        const totalHidden = document.getElementById("total_amount");

        if (totalDisplay) totalDisplay.textContent = newTotal.toFixed(2);
        if (totalHidden) totalHidden.value = newTotal.toFixed(2);

        this.updateTotalsHiddenFields(this.saleTotal);
        this.calculate();

        // Evitamos que este dispatch genere recursión bloqueando el estado
        this.isSyncing = true;
        document.dispatchEvent(
            new CustomEvent("sale:totalUpdated", {
                detail: { total: newTotal },
            }),
        );
        this.isSyncing = false;
    },

    handleRepairTypeChange: function (typeId) {
        // Notificamos el cambio de categoría a través del helper
        dispatchRepairCategoryChanged(typeId || null);

        // El monto ahora se rige por la tabla, pero si existe un mapa base, se puede usar aquí
        // const amounts = window.repairAmountsMap || {};
        // if (typeId && amounts[typeId] !== undefined) {
        //     this.setTotal(amounts[typeId]);
        // }
    },

    refreshTotalFromSource: function () {
        const source =
            this.saleType === SALE_TYPE.REPAIR
                ? this.elements.repairAmount()
                : this.elements.totalAmount();
        this.setTotal(source?.value || 0);
    },

    toggleDualPayment: function (enabled) {
        const el = this.elements;
        const hiddenDual = document.getElementById(
            "hidden_enable_dual_payment",
        );

        if (hiddenDual) hiddenDual.value = enabled ? "1" : "0";

        // --- Control de Interfaz ---
        if (enabled) {
            el.wrapperSingle()?.classList.add("d-none");
            el.wrapperDualInfo()?.classList.remove("d-none");
        } else {
            el.wrapperSingle()?.classList.remove("d-none");
            el.wrapperDualInfo()?.classList.add("d-none");

            if (el.modalAmount2()) {
                el.modalAmount2().value = "0.00";
                this.calculate();
            }
        }

        // Estado de inputs
        if (el.modalAmount1()) el.modalAmount1().readOnly = !enabled;
        if (el.modalAmount2()) el.modalAmount2().readOnly = !enabled;

        // Estado de Choices.js
        [el.modalType1(), el.modalType2()].forEach((select) => {
            if (select?._choices) {
                enabled ? select._choices.enable() : select._choices.disable();
            } else if (select) {
                select.disabled = !enabled;
            }
        });

        // Reset de seguridad si se apaga
        if (!enabled && el.modalAmount2()) {
            el.modalAmount2().value = "0.00";
            this.calculate();
        }
    },

    calculate: function () {
        if (this.isSyncing) return;
        this.isSyncing = true;

        try {
            const el = this.elements;
            const r1 = parseFloat(el.modalAmount1()?.value) || 0;
            const r2 = parseFloat(el.modalAmount2()?.value) || 0;
            const totalReceived = r1 + r2;

            const result = PaymentCalculator.calculate(
                this.saleTotal,
                totalReceived,
            );
            const change = parseFloat(result.change) || 0;
            const balance = parseFloat(result.balance) || 0;

            // Actualización de UI
            if (el.labelTotalReceived())
                el.labelTotalReceived().textContent = totalReceived.toFixed(2);
            if (el.labelChange())
                el.labelChange().textContent = change.toFixed(2);
            if (el.labelRemaining())
                el.labelRemaining().textContent = balance.toFixed(2);
            if (el.summaryAmount1())
                el.summaryAmount1().textContent = r1.toFixed(2);
            if (el.summaryAmount2())
                el.summaryAmount2().textContent = r2.toFixed(2);

            // Sincronización segura de etiquetas
            [1, 2].forEach((num) => {
                const label = el[`summaryType${num}`]();
                const select = el[`modalType${num}`]();
                if (label && select && select.selectedIndex !== -1) {
                    label.textContent =
                        select.options[select.selectedIndex].text;
                }
            });

            // Actualizar hiddens directamente sin disparar eventos extra
            const hChange = document.getElementById("hidden_change_returned");
            const hBalance = document.getElementById(
                "hidden_remaining_balance",
            );
            if (hChange) hChange.value = change.toFixed(2);
            if (hBalance) hBalance.value = balance.toFixed(2);

            this.updateStatusUI(totalReceived, change, balance);
        } finally {
            this.isSyncing = false;
        }
    },

    updateOutputField: function (el, value) {
        if (this.isSyncing) return;
        if (el) {
            this.isSyncing = true;
            el.value = value;
            el.dispatchEvent(new Event("input"));
            this.isSyncing = false;
        }
    },

    updateStatusUI: function (received, change, balance) {
        const status = PaymentCalculator.getStatus(
            this.saleTotal,
            received,
            change,
            balance,
        );

        const badgeHtml = `<span class="badge bg-${status.class}">${status.label}</span>`;

        // 1. Actualizar indicador en el Modal
        const indicator = this.elements.statusIndicator();
        if (indicator) indicator.innerHTML = badgeHtml;

        // 2. Actualizar indicador en el Resumen (Summary)
        const summary = this.elements.summaryStatus();
        if (summary) summary.innerHTML = badgeHtml;

        // Despachar evento por si otros módulos necesitan reaccionar
        document.dispatchEvent(
            new CustomEvent("sale:paymentUpdated", {
                detail: {
                    amountReceived: received,
                    changeReturned: change,
                    remainingBalance: balance,
                    saleTotal: this.saleTotal,
                    status: status,
                },
            }),
        );
    },
    updateTotalsHiddenFields(total) {
        const totals = {};
        const rows = document.querySelectorAll("#order-items-table tbody tr");

        if (rows.length > 0) {
            // Obtenemos la moneda de la primera fila (asumiendo moneda única por ahora)
            const firstRow = rows[0];
            const currency =
                firstRow.querySelector('[name*="[currency]"]')?.value || "1";

            // El valor en el JSON debe ser el total final (ya restado el descuento)
            totals[currency] = parseFloat(total) || 0;
        } else {
            // Si no hay filas (ej. una reparación pura sin productos)
            totals["1"] = parseFloat(total) || 0;
        }

        const source = document.getElementById("totals_source");
        const hidden = document.getElementById("hidden_totals");

        const jsonString = JSON.stringify(totals);
        if (source) source.value = jsonString;
        if (hidden) hidden.value = jsonString;
    },
};

window.salePayment = salePayment;
export default salePayment;
