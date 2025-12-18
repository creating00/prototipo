// resources/js/modules/sales/partials/sale-payment.js

const SALE_TYPE = {
    SALE: "1",
    REPAIR: "2",
};

const salePayment = {
    // Variables para almacenar el total de la venta
    saleTotal: 0,
    saleType: SALE_TYPE.SALE,

    init: function () {
        this.detectSaleType();
        this.bindEvents();
        this.initializePaymentFields();
        this.calculateChangeAndBalance();
    },

    bindEvents: function () {
        const amountReceivedInput = document.getElementById("amount_received");
        if (amountReceivedInput) {
            ["input", "change"].forEach((event) =>
                amountReceivedInput.addEventListener(event, () =>
                    this.calculateChangeAndBalance()
                )
            );
        }

        const saleTypeSelect = document.querySelector(
            'select[name="sale_type"]'
        );
        if (saleTypeSelect) {
            saleTypeSelect.addEventListener("change", () =>
                this.handleSaleTypeChange()
            );
        }

        document.addEventListener("sale:totalUpdated", (event) => {
            if (this.saleType !== SALE_TYPE.REPAIR) {
                this.saleTotal = event.detail.total || 0;
                this.calculateChangeAndBalance();
            }
        });
    },

    detectSaleType: function () {
        const saleTypeSelect = document.querySelector(
            'select[name="sale_type"]'
        );
        if (!saleTypeSelect) return;

        this.saleType = saleTypeSelect.value;
        this.applySaleTypeUI();
    },

    handleSaleTypeChange: function () {
        const saleTypeSelect = document.querySelector(
            'select[name="sale_type"]'
        );
        if (!saleTypeSelect) return;

        this.saleType = saleTypeSelect.value;
        this.applySaleTypeUI();
        this.calculateChangeAndBalance();
    },

    applySaleTypeUI: function () {
        const saleTotalWrapper = document.getElementById("sale-total-wrapper");
        const repairWrapper = document.getElementById("repair-amount-wrapper");
        const repairInput = document.getElementById("repair_amount");

        if (this.saleType === SALE_TYPE.REPAIR) {
            saleTotalWrapper?.classList.add("d-none");
            repairWrapper?.classList.remove("d-none");

            if (repairInput) {
                repairInput.disabled = false;
            }

            this.setSaleTotalFromRepair();
        } else {
            repairWrapper?.classList.add("d-none");
            saleTotalWrapper?.classList.remove("d-none");

            if (repairInput) {
                repairInput.disabled = true;
                repairInput.value = "";
            }

            this.setSaleTotalFromSale();
        }
    },
    setSaleTotalFromRepair: function () {
        const repairInput = document.getElementById("repair_amount");
        this.saleTotal = parseFloat(repairInput?.value) || 0;
        this.calculateChangeAndBalance();
    },

    setSaleTotalFromSale: function () {
        const totalField = document.getElementById("total_amount");
        this.saleTotal = parseFloat(totalField?.value) || 0;
        this.calculateChangeAndBalance();
    },

    initializePaymentFields: function () {
        // Obtener el total inicial del campo total_amount
        const totalField = document.getElementById("total_amount");
        if (totalField) {
            this.saleTotal = parseFloat(totalField.value) || 0;
        }

        // Inicializar campos de cambio y saldo (solo lectura)
        const changeField = document.getElementById("change_returned");
        const balanceField = document.getElementById("remaining_balance");

        if (changeField) changeField.readOnly = true;
        if (balanceField) balanceField.readOnly = true;
    },

    calculateChangeAndBalance: function () {
        const amountReceivedInput = document.getElementById("amount_received");
        const changeReturnedInput = document.getElementById("change_returned");
        const remainingBalanceInput =
            document.getElementById("remaining_balance");

        if (
            !amountReceivedInput ||
            !changeReturnedInput ||
            !remainingBalanceInput
        )
            return;

        const amountReceived = parseFloat(amountReceivedInput.value) || 0;

        // Calcular cambio devuelto (si amount_received > total)
        const changeReturned = Math.max(0, amountReceived - this.saleTotal);
        changeReturnedInput.value = changeReturned.toFixed(2);

        // Calcular saldo pendiente (si total > amount_received)
        const remainingBalance = Math.max(0, this.saleTotal - amountReceived);
        remainingBalanceInput.value = remainingBalance.toFixed(2);

        // Actualizar estado visual
        this.updatePaymentStatusVisual(
            remainingBalance,
            changeReturned,
            amountReceived
        );

        // Disparar evento para que otros componentes sepan que el pago cambió
        this.dispatchPaymentUpdatedEvent(
            amountReceived,
            changeReturned,
            remainingBalance
        );
    },

    updatePaymentStatusVisual: function (
        remainingBalance,
        changeReturned,
        amountReceived
    ) {
        const statusElement = document.getElementById(
            "payment_status_indicator"
        );
        if (!statusElement) return;

        let statusHtml = "";
        let statusClass = "";

        if (
            remainingBalance === 0 &&
            changeReturned === 0 &&
            amountReceived > 0
        ) {
            statusHtml = "Pagado exacto";
            statusClass = "success";
        } else if (remainingBalance === 0 && changeReturned > 0) {
            statusHtml = "Pagado con cambio";
            statusClass = "info";
        } else if (remainingBalance > 0 && amountReceived > 0) {
            statusHtml = "Pago parcial";
            statusClass = "warning";
        } else if (remainingBalance > 0 && amountReceived === 0) {
            statusHtml = "Pendiente de pago";
            statusClass = "danger";
        } else {
            statusHtml = "Sin pago registrado";
            statusClass = "secondary";
        }

        statusElement.innerHTML = `<span class="badge bg-${statusClass}">${statusHtml}</span>`;
    },

    handleCustomerTypeChange: function () {
        const customerTypeSelect = document.querySelector(
            'select[name="customer_type"]'
        );
        const amountReceivedInput = document.getElementById("amount_received");

        if (!customerTypeSelect || !amountReceivedInput) return;

        const customerType = customerTypeSelect.value;

        // Para ventas entre sucursales, el pago podría ser diferido
        if (customerType.includes("Branch")) {
            amountReceivedInput.value = "0";
            amountReceivedInput.readOnly = true;
            amountReceivedInput.title =
                "Para ventas entre sucursales, el pago se registra posteriormente";
            amountReceivedInput.classList.add("bg-light");
        } else {
            amountReceivedInput.readOnly = false;
            amountReceivedInput.title = "";
            amountReceivedInput.classList.remove("bg-light");
        }

        this.calculateChangeAndBalance();
    },

    dispatchPaymentUpdatedEvent: function (
        amountReceived,
        changeReturned,
        remainingBalance
    ) {
        const event = new CustomEvent("sale:paymentUpdated", {
            detail: {
                amountReceived: amountReceived,
                changeReturned: changeReturned,
                remainingBalance: remainingBalance,
                saleTotal: this.saleTotal,
            },
        });
        document.dispatchEvent(event);
    },

    // Método público para que otros componentes actualicen el total
    setSaleTotal: function (total) {
        this.saleTotal = total || 0;
        this.calculateChangeAndBalance();
    },
};

// Hacer disponible globalmente para el atributo oninput
window.salePayment = salePayment;

export default salePayment;
