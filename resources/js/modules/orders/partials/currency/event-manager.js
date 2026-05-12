/**
 * Manejador de eventos del módulo
 * Coordina los listeners y dispatch de eventos personalizados
 */
export class EventManager {
    /**
     * Registra los event listeners
     * @param {Function} calculateCallback
     */
    bindEvents(calculateCallback) {
        document.addEventListener("sale:totalUpdated", () =>
            calculateCallback(),
        );
    }

    /**
     * Dispara el evento de totales calculados
     * @param {{ars: number, usd: number, rate: number}} totals
     */
    dispatchTotalsCalculated({ ars, usd, rate }) {
        document.dispatchEvent(
            new CustomEvent("order:totalsCalculated", {
                detail: {
                    ars,
                    usd,
                    cotizacion: rate,
                },
            }),
        );
    }
}
