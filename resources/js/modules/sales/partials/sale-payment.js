// resources/js/modules/sales/partials/sale-payment.js

import PaymentCalculator from "../services/PaymentCalculator";
import FieldSyncer from "../services/FieldSyncer";
import RepairUiManager from "../services/RepairUiManager";
import { dispatchRepairCategoryChanged } from "@/helpers/repair-category-events";

// Importar constantes
import { SALE_TYPE, FIELDS_TO_SYNC } from "../constants/payment-constants";

// Importar módulos de pago
import PaymentSyncManager from "../payment/PaymentSyncManager";
import PaymentMethodHandler from "../payment/PaymentMethodHandler";
import DualPaymentHandler from "../payment/DualPaymentHandler";
import PaymentUIUpdater from "../payment/PaymentUIUpdater";
import PaymentManager from "../payment/PaymentManager";

/**
 * Helper para manejo de DOM y cache
 */
class DOMHelper {
    constructor() {
        this._domCache = new Map();
    }

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

    getFloat(id) {
        const el = this.el(id, id);
        return parseFloat(el?.value) || 0;
    }
}

/**
 * Helper para manejo de estado de sincronización
 */
class StateManager {
    constructor() {
        this.saleTotal = 0;
        this.saleType = SALE_TYPE.SALE;
        this.isSyncing = false;
    }

    withSync(callback) {
        this.isSyncing = true;
        try {
            callback();
        } finally {
            this.isSyncing = false;
        }
    }
}

/**
 * Clase principal SalePayment - Orquestador
 */
class SalePayment {
    constructor() {
        // Helpers
        this.dom = new DOMHelper();
        this.state = new StateManager();

        // Módulos de pago
        this.syncManager = new PaymentSyncManager(this.dom, this.state);
        this.uiUpdater = new PaymentUIUpdater(this.dom);
        this.methodHandler = new PaymentMethodHandler(
            this.dom,
            this.syncManager,
        );
        this.paymentManager = new PaymentManager(
            this.dom,
            this.state,
            this.syncManager,
            this.uiUpdater,
            this.methodHandler,
        );
        this.dualHandler = new DualPaymentHandler(
            this.dom,
            this.methodHandler,
            () => this.paymentManager.calculate(),
        );

        // Backward compatibility - exponer métodos del DOM helper
        this.el = this.dom.el.bind(this.dom);
        this.getFloat = this.dom.getFloat.bind(this.dom);
        this.setText = this.dom.setText.bind(this.dom);
        this.setHTML = this.dom.setHTML.bind(this.dom);
        this.toggle = this.dom.toggle.bind(this.dom);
    }

    init() {
        this.detectSaleType();
        this.bindEvents();
        FieldSyncer.sync(FIELDS_TO_SYNC);
        this.initialLoad();
    }

    detectSaleType() {
        const select = this.dom.el("saleType", "sale_type");
        if (select) {
            this.state.saleType = select.value;
            RepairUiManager.toggleFields(
                this.state.saleType === SALE_TYPE.REPAIR,
            );
        }
    }

    initialLoad() {
        const subtotal = this.dom.getFloat("subtotal_amount");
        const currentTotal = this.dom.getFloat("total_amount");
        this.state.saleTotal = currentTotal;

        this.dispatchEvent("sale:subtotalUpdated", { subtotal });

        const paymentType = this.dom.el(
            "payType",
            'select[name="payment_type_visible"]',
        );
        if (paymentType) {
            this.methodHandler.updateMethodVisibility(paymentType.value, "");
            this.methodHandler.updateMethodVisibility(paymentType.value, "1");
        }

        const modalType2 = this.dom.el("modalType2", "payment_type_2_modal");
        if (modalType2) {
            this.methodHandler.updateMethodVisibility(modalType2.value, "2");
        }

        const dualCheck = this.dom.el("dualCheck", "enable_dual_payment");
        if (dualCheck) {
            this.dualHandler.toggleDualPayment(dualCheck.checked);
        }

        this.paymentManager.updateTotalsHiddenFields(this.state.saleTotal);
        this.syncManager.syncRepairAmount();
        this.paymentManager.calculate();
    }

    bindEvents() {
        // 1. Sincronización Bidireccional (Vista <-> Modal 1)
        this.syncManager.bindSync(
            this.dom.el("amtRcv", "amount_received"),
            this.dom.el("modal1", "amount_received_1_modal"),
            "input",
        );
        this.syncManager.bindSync(
            this.dom.el("payTypeVis", "payment_type_visible"),
            this.dom.el("modalType1", "payment_type_1_modal"),
            "change",
            true,
        );
        this.syncManager.bindSync(
            this.dom.el("bankVis", "bank_id_visible"),
            this.dom.el("modalBank1", "bank_id_1_modal"),
            "change",
            true,
        );
        this.syncManager.bindSync(
            this.dom.el("accVis", "bank_account_id_visible"),
            this.dom.el("modalAcc1", "bank_account_id_1_modal"),
            "change",
            true,
        );

        // 2. Visibilidad y Polimorfismo
        this.on("payTypeVis", "payment_type_visible", "change", (e) => {
            this.methodHandler.updateMethodVisibility(e.target.value, "");
        });

        this.on("modalType1", "payment_type_1_modal", "change", (e) => {
            this.methodHandler.updateMethodVisibility(e.target.value, "1");
            this.dualHandler.checkDualLabels();
        });

        this.on("modalType2", "payment_type_2_modal", "change", (e) => {
            this.methodHandler.updateMethodVisibility(e.target.value, "2");
            this.dualHandler.checkDualLabels();
        });

        // 3. Pago Doble
        this.on("dualCheck", "enable_dual_payment", "change", (e) =>
            this.dualHandler.toggleDualPayment(e.target.checked),
        );

        // 4. Cálculos
        [
            "amount_received",
            "amount_received_1_modal",
            "amount_received_2_modal",
            "exchange_rate_blue",
        ].forEach((id) => {
            this.on(id, id, "input", () => {
                this.paymentManager.calculate();
                this.paymentManager.updateTotalsHiddenFields(
                    this.state.saleTotal,
                );
            });
        });

        // 5. Cambios de tipo y descuentos
        this.on("saleType", "sale_type", "change", (e) =>
            this.paymentManager.handleSaleTypeChange(
                e.target.value,
                RepairUiManager,
            ),
        );

        this.on("repairType", "repair_type_id", "change", (e) =>
            this.handleRepairTypeChange(e.target.value),
        );

        this.on("discount", "discount_amount_input", "input", () =>
            this.paymentManager.applyManualDiscount(),
        );

        this.on("payDollars", "pay_in_dollars", "change", () =>
            this.paymentManager.updateTotalsHiddenFields(this.state.saleTotal),
        );

        // 6. Custom Events
        document.addEventListener("sale:subtotalUpdated", () =>
            this.paymentManager.applyManualDiscount(),
        );

        document.addEventListener("sale:totalUpdated", (e) =>
            this.paymentManager.setTotal(e.detail.total),
        );
    }

    // Event binding helper
    on(cacheKey, id, event, handler) {
        const el = this.dom.el(cacheKey, id);
        el?.addEventListener(event, handler);
    }

    // Métodos delegados al PaymentManager (para backward compatibility)
    calculate() {
        this.paymentManager.calculate();
    }

    setTotal(value) {
        this.paymentManager.setTotal(value);
    }

    updateTotalsHiddenFields(total) {
        this.paymentManager.updateTotalsHiddenFields(total);
    }

    // Métodos delegados al SyncManager
    syncRepairAmount() {
        this.syncManager.syncRepairAmount();
    }

    // Métodos delegados al MethodHandler
    updateMethodVisibility(paymentType, suffix) {
        this.methodHandler.updateMethodVisibility(paymentType, suffix);
    }

    // Métodos delegados al DualHandler
    toggleDualPayment(enabled) {
        this.dualHandler.toggleDualPayment(enabled);
    }

    // Métodos delegados al UIUpdater
    updateTotalDisplay(total) {
        this.uiUpdater.updateTotalDisplay(total);
    }

    // Otros métodos
    handleRepairTypeChange(typeId) {
        dispatchRepairCategoryChanged(typeId || null);
    }

    refreshTotalFromSource() {
        this.paymentManager.refreshTotalFromSource();
    }

    dispatchEvent(eventName, detail) {
        document.dispatchEvent(new CustomEvent(eventName, { detail }));
    }

    // Backward compatibility - exponer isSyncing y withSync
    get isSyncing() {
        return this.state.isSyncing;
    }

    withSync(callback) {
        this.state.withSync(callback);
    }
}

const salePayment = new SalePayment();
window.salePayment = salePayment;
export default salePayment;
