// resources/js/modules/sales/partials/sale-discount.js

const saleDiscount = {
    subtotal: 0,
    discountMap: window.DISCOUNT_AMOUNT_MAP || {},
    choicesInstance: null,

    init() {
        this.initChoices();
        this.bindEvents();
        this.readInitialSubtotal();
        this.recalculate();
    },

    initChoices() {
        const element = document.getElementById("discount_id");
        if (element && window.Choices) {
            this.choicesInstance = new window.Choices(element, {
                searchEnabled: true,
                itemSelectText: "",
                allowHTML: true,
                shouldSort: false,
                placeholder: true,
                placeholderValue: "Seleccione un descuento",
            });
        }
    },

    bindEvents() {
        document.addEventListener("sale:subtotalUpdated", (event) => {
            this.subtotal = parseFloat(event.detail.subtotal) || 0;
            this.recalculate();
        });

        const element = document.getElementById("discount_id");
        if (element) {
            element.addEventListener("change", () => this.recalculate());
        }
    },

    readInitialSubtotal() {
        const input = document.getElementById("subtotal_amount");
        if (input) this.subtotal = parseFloat(input.value) || 0;
    },

    recalculate() {
        const discountSelect = document.getElementById("discount_id");
        const discountAmountInput = document.getElementById("discount_amount");
        const totalAmountInput = document.getElementById("total_amount");
        const hiddenDiscount = document.getElementById("hidden_discount_id");

        const discountAmountDisplay = document.getElementById(
            "discount_amount_display"
        );
        const totalAmountDisplay = document.getElementById(
            "total_amount_display"
        );

        if (!discountAmountInput || !totalAmountInput) return;

        // Obtenemos el ID seleccionado una sola vez
        const selectedId = discountSelect ? discountSelect.value : "";

        // Sincronizamos con el campo hidden para que el backend lo reciba
        if (hiddenDiscount) {
            hiddenDiscount.value = selectedId;
        }

        let appliedDiscount = 0;

        if (selectedId && this.discountMap[selectedId] !== undefined) {
            const discount = this.discountMap[selectedId];
            const subtotal = this.subtotal;

            if (discount.type === 1) {
                // Monto Fijo
                appliedDiscount = discount.value;
            } else if (discount.type === 2) {
                // Porcentaje
                appliedDiscount = (subtotal * discount.value) / 100;
                if (discount.max !== null && discount.max !== undefined) {
                    appliedDiscount = Math.min(appliedDiscount, discount.max);
                }
            }
        }

        appliedDiscount = Math.min(appliedDiscount, this.subtotal);
        const finalTotal = Math.max(0, this.subtotal - appliedDiscount);

        // Actualizamos los valores de los INPUTS
        discountAmountInput.value = appliedDiscount.toFixed(2);
        totalAmountInput.value = finalTotal.toFixed(2);

        // Actualizamos el DISPLAY visual
        if (discountAmountDisplay) {
            discountAmountDisplay.textContent = appliedDiscount.toFixed(2);
        }

        if (totalAmountDisplay) {
            totalAmountDisplay.textContent = finalTotal.toFixed(2);
        }

        // Notificamos a otros componentes (como el calculador de pago/cambio)
        document.dispatchEvent(
            new CustomEvent("sale:totalUpdated", {
                detail: { total: finalTotal },
            })
        );
        document.dispatchEvent(
            new CustomEvent("sale:discountUpdated", {
                detail: { discount: appliedDiscount },
            })
        );
    },
};

window.saleDiscount = saleDiscount;
export default saleDiscount;
