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
        // Solo cargamos el total inicial y ejecutamos el primer cálculo
        const currentTotal = this.dom.getFloat("total_amount");
        this.state.saleTotal = currentTotal;

        // Si es reparación, ajustar UI
        this.detectSaleType();

        // Calcular inicialmente para que el bloqueo de inputs (_handleInputsLock)
        // se active si ya vienen pagos desde la base de datos (Edit mode)
        this.paymentManager.calculate();
        this.paymentManager.updateTotalsHiddenFields(this.state.saleTotal);
    }

    bindEvents() {
        // 1. Inputs principales de montos (Efectivo, Transferencia, Tarjeta)
        [
            "amount_received_cash",
            "amount_received_transfer",
            "amount_received_card",
        ].forEach((id) => {
            this.on(id, id, "input", () => {
                this.paymentManager.calculate();
            });
        });

        // 2. Selectores de Entidades (Bancos y Cuentas)
        this.on("bank_id_card", "bank_id_card", "change", () =>
            this.paymentManager.calculate(),
        );

        this.on(
            "bank_account_id_transfer",
            "bank_account_id_transfer",
            "change",
            () => this.paymentManager.calculate(),
        );

        // 3. Configuración de Moneda y Tasa
        this.on("exchange_rate_blue", "exchange_rate_blue", "input", () => {
            this.paymentManager.calculate();
            this.paymentManager.updateTotalsHiddenFields(this.state.saleTotal);
        });

        this.on("payDollars", "pay_in_dollars", "change", () => {
            this.paymentManager.updateTotalsHiddenFields(this.state.saleTotal);
        });

        // 4. Lógica de Venta y Descuentos
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

        // 5. Eventos de Sistema / Globales
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
