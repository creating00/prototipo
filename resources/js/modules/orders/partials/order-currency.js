// partials/order-currency.js

const orderCurrency = {
    dollarPrice: 0,

    init() {
        this.dollarInput = document.getElementById("current_dollar_price");
        this.totalUsdInput = document.getElementById("total_amount_usd");
        this.totalArsInput = document.getElementById("total_amount");

        if (!this.dollarInput) return;

        this.fetchDollarPrice();
        this.listenToTotalChanges();
    },

    async fetchDollarPrice() {
        try {
            const response = await fetch(
                "https://dolarapi.com/v1/dolares/blue"
            );
            const data = await response.json();

            if (data && data.venta) {
                this.dollarPrice = data.venta;
                this.dollarInput.value = this.dollarPrice.toLocaleString(
                    "es-AR",
                    { minimumFractionDigits: 2 }
                );
                // Calcular inicial si ya hay un total
                this.calculateTotalUsd(
                    parseFloat(this.totalArsInput.value) || 0
                );
            }
        } catch (error) {
            console.error("Error DolarApi:", error);
            this.dollarInput.value = "Error";
        }
    },

    listenToTotalChanges() {
        // Escucha el evento que ya disparas en orderItems.updateTotal()
        document.addEventListener("sale:totalUpdated", (e) => {
            this.calculateTotalUsd(e.detail.total);
        });
    },

    calculateTotalUsd(arsTotal) {
        if (this.dollarPrice > 0 && this.totalUsdInput) {
            const converted = arsTotal / this.dollarPrice;
            this.totalUsdInput.value = converted.toLocaleString("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }
    },
};

export default orderCurrency;
