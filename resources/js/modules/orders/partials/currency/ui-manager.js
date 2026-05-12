/**
 * Manejador de UI para actualizar elementos del DOM
 * Responsable de renderizar valores en la interfaz
 */
import { SELECTORS } from "./config.js";

export class UIManager {
    constructor() {
        this.elements = {};
        this.cacheElements();
    }

    /**
     * Cachea referencias a elementos del DOM
     */
    cacheElements() {
        Object.entries(SELECTORS).forEach(([key, id]) => {
            this.elements[key] =
                key === "rows" ? null : document.getElementById(id);
        });
    }

    /**
     * Actualiza los inputs de la cotización
     * @param {number} rate
     */
    updateRateInputs(rate) {
        // Input visible
        if (this.elements.dollarInput) {
            this.elements.dollarInput.value = this.formatNumber(rate, "es-AR");
        }

        // Input hidden para el backend
        if (this.elements.exchangeRate) {
            this.elements.exchangeRate.value = rate;
        }
    }

    /**
     * Actualiza las etiquetas de subtotales
     * @param {number} ars
     * @param {number} usd
     */
    updateLabels(ars, usd) {
        if (this.elements.labelArs) {
            this.elements.labelArs.innerText = `$ ${ars.toFixed(2)}`;
        }
        if (this.elements.labelUsd) {
            this.elements.labelUsd.innerText = `U$D ${usd.toFixed(2)}`;
        }
    }

    /**
     * Actualiza los inputs de totales
     * @param {{ars: number, usd: number}} totals
     */
    updateTotalInputs({ ars, usd }) {
        if (this.elements.totalArs) {
            this.elements.totalArs.value = ars.toFixed(2);
            this.elements.totalArs.dispatchEvent(new Event("input"));
        }

        if (this.elements.totalUsd) {
            this.elements.totalUsd.value = this.formatNumber(usd, "en-US");
        }
    }

    /**
     * Formatea un número según locale
     * @param {number} value
     * @param {string} locale
     * @returns {string}
     */
    formatNumber(value, locale) {
        return value.toLocaleString(locale, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    /**
     * Obtiene un elemento específico
     * @param {string} key
     * @returns {HTMLElement|null}
     */
    getElement(key) {
        return this.elements[key];
    }
}
