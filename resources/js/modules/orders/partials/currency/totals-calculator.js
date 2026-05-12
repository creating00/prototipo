/**
 * Calculadora de totales en múltiples monedas
 * Maneja la lógica de suma y conversión de montos
 */
import { CONFIG, SELECTORS } from "./config.js";

export class TotalsCalculator {
    constructor(dollarPrice) {
        this.dollarPrice = dollarPrice;
    }

    /**
     * Actualiza el precio del dólar
     * @param {number} rate
     */
    setRate(rate) {
        this.dollarPrice = rate;
    }

    /**
     * Calcula los subtotales separados por moneda
     * @returns {{sumArs: number, sumUsd: number}}
     */
    calculateSubtotals() {
        const rows = document.querySelectorAll(SELECTORS.rows);
        let sumArs = 0;
        let sumUsd = 0;

        rows.forEach((row) => {
            const subtotal = this.getRowSubtotal(row);
            const currency = this.getRowCurrency(row);

            if (currency === CONFIG.CURRENCY_CODES.USD) {
                sumUsd += subtotal;
            } else {
                sumArs += subtotal;
            }
        });

        return { sumArs, sumUsd };
    }

    /**
     * Obtiene el subtotal de una fila
     * @param {HTMLElement} row
     * @returns {number}
     */
    getRowSubtotal(row) {
        return parseFloat(row.querySelector(".subtotal")?.value) || 0;
    }

    /**
     * Obtiene la moneda de una fila
     * @param {HTMLElement} row
     * @returns {string}
     */
    getRowCurrency(row) {
        return row.querySelector('input[name*="[currency]"]')?.value;
    }

    /**
     * Convierte los subtotales a totales en ambas monedas
     * @param {number} sumArs
     * @param {number} sumUsd
     * @returns {{ars: number, usd: number, rate: number}}
     */
    convertTotals(sumArs, sumUsd) {
        const rate = this.dollarPrice || 0;

        return {
            ars: sumArs + sumUsd * rate,
            usd: rate > 0 ? sumUsd + sumArs / rate : sumUsd,
            rate,
        };
    }

    /**
     * Calcula todos los totales
     * @returns {{subtotals: {sumArs: number, sumUsd: number}, totals: {ars: number, usd: number, rate: number}}}
     */
    calculate() {
        const subtotals = this.calculateSubtotals();
        const totals = this.convertTotals(subtotals.sumArs, subtotals.sumUsd);

        return { subtotals, totals };
    }
}