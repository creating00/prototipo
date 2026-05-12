const CurrencyLoader = {
    /**
     * Inicializa la cotización.
     * @param {string} type - venta | compra | promedio (opcional, default venta)
     * @returns {number|null}
     */
    async init(type = "venta") {
        const rate = await this.fetchRate(type);

        if (rate) {
            this.updateInputs(rate);
        }

        return rate;
    },

    /**
     * Obtiene la cotización desde backend
     */
    async fetchRate(type = "venta") {
        try {
            const response = await fetch(`/api/currency/rate?type=${type}`);
            const data = await response.json();

            return data?.rate ?? null;
        } catch (e) {
            console.error("Error cargando cotización:", e);
            return null;
        }
    },

    /**
     * Actualiza TODOS los inputs dependientes de la cotización
     */
    updateInputs(rate) {
        // Input visible para el usuario (formateado)
        const display = document.getElementById("current_dollar_price");
        if (display) {
            display.value = rate.toLocaleString("es-AR", {
                minimumFractionDigits: 2,
            });
        }

        // Input operativo (lo usan ConvertPayment / SalePayment)
        const blue = document.getElementById("exchange_rate_blue");
        if (blue) {
            blue.value = rate;

            // Disparar evento para recalcular todo lo dependiente
            blue.dispatchEvent(new Event("input", { bubbles: true }));
        }

        // Hidden para Laravel / backend
        const hidden = document.getElementById("exchange_rate");
        if (hidden) {
            hidden.value = rate;
        }
    },
};

export default CurrencyLoader;
