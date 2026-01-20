const orderCurrency = {
    dollarPrice: 0,

    // Selectores centralizados para fácil mantenimiento
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

    async syncRateWithBackend() {
        try {
            const response = await fetch("/api/currency/rate");
            const data = await response.json();

            if (data?.rate) {
                this.dollarPrice = data.rate;
                this.elements.dollarInput.value = this.formatCurrency(
                    data.rate,
                    "es-AR",
                );
                this.calculateTotal();
                return data.rate;
            }
        } catch (error) {
            console.log("Using frontend API rate");
            return null;
        }
    },

    async fetchDollarPrice() {
        // Estrategia: 1. Backend (cacheado), 2. API directa, 3. Fallback
        const strategies = [
            { name: "backend", fn: () => this.syncRateWithBackend() },
            { name: "api", fn: () => this.fetchFromDolarApi() },
        ];

        for (const strategy of strategies) {
            try {
                const rate = await strategy.fn();
                if (rate && rate > 0) {
                    //console.log(`Rate obtained from ${strategy.name}: ${rate}`);
                    return; // Salir si obtuvimos una tasa válida
                }
            } catch (error) {
                console.warn(`Strategy ${strategy.name} failed:`, error);
            }
        }

        // Si todo falla, usar fallback
        this.useFallbackRate();
    },

    async fetchFromDolarApi() {
        try {
            const response = await fetch(
                "https://dolarapi.com/v1/dolares/blue",
            );
            const data = await response.json();

            if (data?.venta) {
                this.dollarPrice = data.venta;
                this.elements.dollarInput.value = this.formatCurrency(
                    this.dollarPrice,
                    "es-AR",
                );
                this.calculateTotal();
            }
        } catch (error) {
            console.error("DolarApi Error:", error);
            this.elements.dollarInput.value = "Error";
        }
    },

    useFallbackRate() {
        this.dollarPrice = 1000; // Fallback
        this.elements.dollarInput.value = this.formatCurrency(
            this.dollarPrice,
            "es-AR",
        );
        this.calculateTotal();
        console.warn("Using fallback exchange rate: 1000");
    },

    calculateTotal() {
        let sumArs = 0;
        let sumUsd = 0;

        document.querySelectorAll(this.selectors.rows).forEach((row) => {
            const subtotal =
                parseFloat(row.querySelector(".subtotal")?.value) || 0;
            const currency = row.querySelector(
                'input[name*="[currency]"]',
            )?.value;

            currency === "USD" ? (sumUsd += subtotal) : (sumArs += subtotal);
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

        // Totales cruzados
        const totalArs = sumArs + sumUsd * cotizacion;
        const totalUsd = cotizacion > 0 ? sumUsd + sumArs / cotizacion : sumUsd;

        // Update UI
        if (this.elements.totalArs) {
            this.elements.totalArs.value = totalArs.toFixed(2);
            // Notificar a otros scripts (ej. sale-payment.js)
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
