/**
 * Order Currency Manager - Orquestador Principal
 * Ensambla y coordina todos los módulos del sistema de monedas
 */
import { CONFIG } from "./currency/config.js";
import { ExchangeRateService } from "./currency/exchange-rate-service.js";
import { TotalsCalculator } from "./currency/totals-calculator.js";
import { UIManager } from "./currency/ui-manager.js";
import { EventManager } from "./currency/event-manager.js";

class OrderCurrency {
    constructor() {
        this.dollarPrice = 0;
        this.rateType = CONFIG.RATE_TYPES.BRANCH;

        // Inicializar módulos
        this.uiManager = new UIManager();
        this.eventManager = new EventManager();
        this.calculator = null; // Se inicializa cuando tengamos el rate
        this.rateService = null; // Se inicializa cuando sepamos el tipo
    }

    // ==================== Inicialización ====================

    /**
     * Inicializa el módulo completo
     */
    init() {
        this.determineRateType();
        this.loadExchangeRate();
        this.bindEvents();
        this.calculateTotal();
    }

    /**
     * Determina el tipo de cotización según el tipo de cliente
     */
    determineRateType() {
        const customerType =
            this.uiManager.getElement("customerType")?.value || "";
        const isClient = customerType.endsWith("\\Client");

        this.rateType = isClient
            ? CONFIG.RATE_TYPES.CLIENT
            : CONFIG.RATE_TYPES.BRANCH;

        // Inicializar servicio con el tipo correcto
        this.rateService = new ExchangeRateService(this.rateType);
    }

    /**
     * Carga la cotización inicial
     */
    loadExchangeRate() {
        const isEdit = this.uiManager.getElement("isEdit")?.value === "1";
        const storedRate = this.uiManager.getElement("exchangeRate")?.value;
        const isBranch = this.rateType === CONFIG.RATE_TYPES.BRANCH;

        // Branch en edit mode reutiliza la cotización guardada
        if (isBranch && isEdit && storedRate) {
            this.updateRate(parseFloat(storedRate));
        } else {
            // Client siempre fetch / Branch en create mode fetch
            this.fetchExchangeRate();
        }
    }

    /**
     * Registra los event listeners
     */
    bindEvents() {
        this.eventManager.bindEvents(() => this.calculateTotal());
    }

    // ==================== Obtención de Cotización ====================

    /**
     * Obtiene la cotización usando el servicio
     */
    async fetchExchangeRate() {
        const rate = await this.rateService.fetchRate();

        if (rate) {
            this.updateRate(rate);
        } else {
            const fallbackRate = this.rateService.getFallbackRate();
            this.updateRate(fallbackRate);
        }
    }

    // ==================== Actualización ====================

    /**
     * Actualiza la cotización y recalcula
     * @param {number} rate
     */
    updateRate(rate) {
        this.dollarPrice = rate;

        // Actualizar UI
        this.uiManager.updateRateInputs(rate);

        // Actualizar calculadora con el nuevo rate
        if (!this.calculator) {
            this.calculator = new TotalsCalculator(rate);
        } else {
            this.calculator.setRate(rate);
        }

        // Recalcular totales
        this.calculateTotal();
    }

    // ==================== Cálculo de Totales ====================

    /**
     * Calcula y renderiza todos los totales
     */
    calculateTotal() {
        // Si aún no hay calculadora, inicializarla
        if (!this.calculator) {
            this.calculator = new TotalsCalculator(this.dollarPrice);
        }

        // Calcular
        const { subtotals, totals } = this.calculator.calculate();

        // Renderizar
        this.renderResults(subtotals, totals);
    }

    /**
     * Renderiza los resultados en la UI
     * @param {{sumArs: number, sumUsd: number}} subtotals
     * @param {{ars: number, usd: number, rate: number}} totals
     */
    renderResults(subtotals, totals) {
        // Actualizar etiquetas de subtotales
        this.uiManager.updateLabels(subtotals.sumArs, subtotals.sumUsd);

        // Actualizar inputs de totales
        this.uiManager.updateTotalInputs(totals);

        // Disparar evento para otros módulos
        this.eventManager.dispatchTotalsCalculated(totals);
    }
}

// Exportar instancia singleton para compatibilidad con código existente
const orderCurrency = new OrderCurrency();
export default orderCurrency;
