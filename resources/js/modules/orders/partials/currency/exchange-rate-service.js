/**
 * Servicio para obtener cotizaciones de dólar
 * Maneja múltiples estrategias de fetch (backend, API externa)
 */
import { CONFIG } from "./config.js";

export class ExchangeRateService {
    constructor(rateType) {
        this.rateType = rateType;
    }

    /**
     * Obtiene la cotización usando estrategias en cascada
     * @returns {Promise<number|null>}
     */
    async fetchRate() {
        const strategies = [
            { name: "backend", fn: () => this.fetchFromBackend() },
            { name: "api", fn: () => this.fetchFromDolarApi() },
        ];

        for (const { name, fn } of strategies) {
            try {
                const rate = await fn();
                if (this.isValidRate(rate)) {
                    return rate;
                }
            } catch (error) {
                console.warn(`Estrategia ${name} falló:`, error);
            }
        }

        return null;
    }

    /**
     * Obtiene la cotización desde el backend
     * @returns {Promise<number|null>}
     */
    async fetchFromBackend() {
        const response = await fetch(
            `/api/currency/rate?type=${this.rateType}`,
        );
        const { rate } = await response.json();
        return rate || null;
    }

    /**
     * Obtiene la cotización desde DolarAPI
     * @returns {Promise<number|null>}
     */
    async fetchFromDolarApi() {
        const response = await fetch("https://dolarapi.com/v1/dolares/blue");
        const { venta } = await response.json();
        return venta || null;
    }

    /**
     * Valida si una cotización es válida
     * @param {number|null} rate
     * @returns {boolean}
     */
    isValidRate(rate) {
        return rate && rate > 0;
    }

    /**
     * Retorna la cotización de fallback
     * @returns {number}
     */
    getFallbackRate() {
        console.warn(`Using fallback exchange rate: ${CONFIG.FALLBACK_RATE}`);
        return CONFIG.FALLBACK_RATE;
    }
}
