// resources/js/modules/sales/partials/sale-payment.js
import PaymentCalculator from "../services/PaymentCalculator";
import FieldSyncer from "../services/FieldSyncer";
import RepairUiManager from "../services/RepairUiManager";
import { dispatchRepairCategoryChanged } from "@/helpers/repair-category-events";

const SALE_TYPE = { SALE: "1", REPAIR: "2" };

const salePayment = {
    saleTotal: 0,
    saleType: SALE_TYPE.SALE,

    elements: {
        saleType: () => document.querySelector('select[name="sale_type"]'),
        repairType: () =>
            document.querySelector('select[name="repair_type_id"]') ||
            document.getElementById("repair_type"),
        repairAmount: () => document.getElementById("repair_amount"),
        amountReceived: () => document.getElementById("amount_received"),
        totalAmount: () => document.getElementById("total_amount"),
        changeReturned: () => document.getElementById("change_returned"),
        remainingBalance: () => document.getElementById("remaining_balance"),
        statusIndicator: () =>
            document.getElementById("payment_status_indicator"),
        summaryStatus: () => document.getElementById("summary_payment_status"),
        discountInput: () => document.getElementById("discount_amount_input"),
        subtotalHidden: () => document.getElementById("subtotal_amount"),
        discountHidden: () => document.getElementById("hidden_discount_amount"),
    },

    fieldsToSync: {
        sale_date: "hidden_sale_date",
        amount_received: "hidden_amount_received",
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
        // 1. Obtener valores de Blade
        const subtotal = parseFloat(this.elements.subtotalHidden()?.value) || 0;

        // 2. Notificar subtotal inicial
        document.dispatchEvent(
            new CustomEvent("sale:subtotalUpdated", {
                detail: { subtotal: subtotal },
            }),
        );

        // 3. Resetear saleTotal para que applyManualDiscount no se bloquee por el IF
        this.saleTotal = -1;

        // 4. Ejecutar el cálculo completo
        // Esto disparará discountUpdated, totalUpdated y actualizará la UI
        this.applyManualDiscount();
    },

    bindEvents: function () {
        this.elements
            .saleType()
            ?.addEventListener("change", (e) =>
                this.handleSaleTypeChange(e.target.value),
            );
        this.elements
            .repairType()
            ?.addEventListener("change", (e) =>
                this.handleRepairTypeChange(e.target.value),
            );
        this.elements
            .amountReceived()
            ?.addEventListener("input", () => this.calculate());

        this.elements.discountInput()?.addEventListener("input", () => {
            this.applyManualDiscount();
        });

        document.addEventListener("sale:subtotalUpdated", (e) => {
            this.applyManualDiscount();
        });

        document.addEventListener("sale:totalUpdated", (e) =>
            this.setTotal(e.detail.total),
        );
    },

    setTotal: function (value) {
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
        const subtotalEl = this.elements.subtotalHidden();
        const discountEl = this.elements.discountInput();

        if (!subtotalEl) return;

        const subtotal = parseFloat(subtotalEl.value) || 0;
        const discount = parseFloat(discountEl?.value) || 0;
        const newTotal = Math.max(0, subtotal - discount);

        // 1. NOTIFICAR SIEMPRE EL DESCUENTO (para que el Summary lo pinte)
        document.dispatchEvent(
            new CustomEvent("sale:discountUpdated", {
                detail: { discount: discount },
            }),
        );

        // 2. Si el total no cambió, igual terminamos pero después de haber notificado el descuento
        if (this.saleTotal === newTotal && newTotal !== 0) {
            // Solo retornamos si ya tenemos un total calculado y es igual
            // Pero llamamos a calculate para asegurar que cambio/saldo estén al día
            this.calculate();
            return;
        }

        this.saleTotal = newTotal;

        // Actualizar UI
        const totalDisplay = document.getElementById("total_amount_display");
        const totalHidden = document.getElementById("total_amount");

        if (totalDisplay) totalDisplay.textContent = newTotal.toFixed(2);
        if (totalHidden) totalHidden.value = newTotal.toFixed(2);

        this.updateTotalsHiddenFields(this.saleTotal);
        this.calculate();

        document.dispatchEvent(
            new CustomEvent("sale:totalUpdated", {
                detail: { total: newTotal },
            }),
        );
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

    calculate: function () {
        const received = parseFloat(this.elements.amountReceived()?.value) || 0;
        const result = PaymentCalculator.calculate(this.saleTotal, received);

        this.updateOutputField(this.elements.changeReturned(), result.change);
        this.updateOutputField(
            this.elements.remainingBalance(),
            result.balance,
        );
        this.updateStatusUI(received, result.change, result.balance);
    },

    updateOutputField: function (el, value) {
        if (el) {
            el.value = value;
            el.dispatchEvent(new Event("input"));
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
