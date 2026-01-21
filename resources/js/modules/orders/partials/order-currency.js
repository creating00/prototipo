const orderCurrency = {
    dollarPrice: 0,

    selectors: {
        dollarInput: "current_dollar_price",
        totalUsd: "total_amount_usd",
        totalArs: "total_amount",
        labelArs: "subtotal_ars_pure",
        labelUsd: "subtotal_usd_pure",
        rows: "#order-items-table tr[data-id]",
    },

    init() {
        this.cacheElements();
        if (!this.elements.dollarInput) return;

        this.fetchDollarPrice();
        this.bindEvents();
    },

    cacheElements() {
        this.elements = {};
        Object.entries(this.selectors).forEach(([key, id]) => {
            this.elements[key] =
                key === "rows" ? null : document.getElementById(id);
        });
    },

    bindEvents() {
        document.addEventListener("sale:totalUpdated", () =>
            this.calculateTotal(),
        );
    },

    async fetchDollarPrice() {
        // Prioridad 1: Backend (mantiene consistencia con la base de datos)
        // Prioridad 2: API Directa (emergencia)
        const strategies = [
            { name: "backend", fn: () => this.syncRateWithBackend() },
            { name: "api", fn: () => this.fetchFromDolarApi() },
        ];

        for (const strategy of strategies) {
            try {
                const rate = await strategy.fn();
                if (rate && rate > 0) {
                    console.log(`Cotización obtenida vía: ${strategy.name}`);
                    return;
                }
            } catch (error) {
                console.warn(`Estrategia ${strategy.name} falló:`, error);
            }
        }

        this.useFallbackRate();
    },

    async syncRateWithBackend() {
        try {
            const response = await fetch("/api/currency/rate");
            const data = await response.json();

            if (data?.rate) {
                this.updateDollarUI(data.rate);
                return data.rate;
            }
        } catch (error) {
            return null;
        }
    },

    async fetchFromDolarApi() {
        try {
            const response = await fetch(
                "https://dolarapi.com/v1/dolares/blue",
            );
            const data = await response.json();

            if (data?.venta) {
                this.updateDollarUI(data.venta);
                return data.venta;
            }
        } catch (error) {
            console.error("DolarApi Error:", error);
            return null;
        }
    },

    updateDollarUI(rate) {
        this.dollarPrice = rate;
        this.elements.dollarInput.value = this.formatCurrency(rate, "es-AR");
        this.calculateTotal();
    },

    useFallbackRate() {
        this.updateDollarUI(1000);
        console.warn("Using fallback exchange rate: 1000");
    },

    calculateTotal() {
        let sumArs = 0;
        let sumUsd = 0;

        document.querySelectorAll(this.selectors.rows).forEach((row) => {
            const subtotal =
                parseFloat(row.querySelector(".subtotal")?.value) || 0;
            const currencyValue = row.querySelector(
                'input[name*="[currency]"]',
            )?.value;

            // Ajuste: El Enum de PHP envía "2" para USD y "1" para ARS
            if (currencyValue === "2") {
                sumUsd += subtotal;
            } else {
                sumArs += subtotal;
            }
        });

        this.updateLabels(sumArs, sumUsd);
        this.renderTotals(sumArs, sumUsd);
    },

    updateLabels(ars, usd) {
        if (this.elements.labelArs)
            this.elements.labelArs.innerText = `$ ${ars.toFixed(2)}`;
        if (this.elements.labelUsd)
            this.elements.labelUsd.innerText = `U$D ${usd.toFixed(2)}`;
    },

    renderTotals(sumArs, sumUsd) {
        const cotizacion = this.dollarPrice || 0;

        const totalArs = sumArs + sumUsd * cotizacion;
        const totalUsd = cotizacion > 0 ? sumUsd + sumArs / cotizacion : sumUsd;

        if (this.elements.totalArs) {
            this.elements.totalArs.value = totalArs.toFixed(2);
            this.elements.totalArs.dispatchEvent(new Event("input"));
        }

        if (this.elements.totalUsd) {
            this.elements.totalUsd.value = this.formatCurrency(
                totalUsd,
                "en-US",
            );
        }

        this.dispatchTotals(totalArs, totalUsd, cotizacion);
    },

    formatCurrency(value, locale) {
        return value.toLocaleString(locale, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    },

    dispatchTotals(ars, usd, cotizacion) {
        document.dispatchEvent(
            new CustomEvent("order:totalsCalculated", {
                detail: { ars, usd, cotizacion },
            }),
        );
    },
};

export default orderCurrency;
