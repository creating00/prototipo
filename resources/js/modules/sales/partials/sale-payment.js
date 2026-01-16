// resources/js/modules/sales/partials/sale-payment.js
import { dispatchRepairCategoryChanged } from "@/helpers/repair-category-events";

const SALE_TYPE = {
    SALE: "1",
    REPAIR: "2",
};

const salePayment = {
    // Variables para almacenar el total de la venta
    saleTotal: 0,
    saleType: SALE_TYPE.SALE,

    // Configuración de sincronización (ID Modal -> ID Hidden)
    fieldsToSync: {
        sale_date: "hidden_sale_date",
        amount_received: "hidden_amount_received",
        change_returned: "hidden_change_returned",
        remaining_balance: "hidden_remaining_balance",
        repair_amount: "hidden_repair_amount",
        discount_id: "hidden_discount_id",
        payment_type_modal: "hidden_payment_type",
    },

    init: function () {
        this.detectSaleType();
        this.bindEvents();
        this.syncModalFields();
        dispatchRepairCategoryChanged(
            this.saleType === SALE_TYPE.REPAIR
                ? document.querySelector('select[name="repair_type_id"]')
                      ?.value || null
                : null
        );
        this.initializePaymentFields();
        this.calculateChangeAndBalance();
    },

    bindEvents: function () {
        // --- 1. Eventos de Reparación ---
        const repairTypeSelect =
            document.getElementById("repair_type") ||
            document.querySelector('select[name="repair_type_id"]');

        if (repairTypeSelect) {
            repairTypeSelect.addEventListener("change", (e) => {
                const typeId = e.target.value;
                const amounts = window.repairAmountsMap || {};
                const repairInput = document.getElementById("repair_amount");

                if (typeId && amounts[typeId] !== undefined && repairInput) {
                    repairInput.value = amounts[typeId];
                    this.setSaleTotalFromRepair();
                }
                dispatchRepairCategoryChanged(typeId || null);
            });
        }

        // --- 2. Eventos de Monto Recibido ---
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

        // --- 3. Escuchar actualizaciones de Total ---
        document.addEventListener("sale:totalUpdated", (event) => {
            // Bloqueo: Si es reparación, solo aceptamos eventos marcados como reparación
            if (this.saleType === SALE_TYPE.REPAIR && !event.detail.isRepair) {
                return;
            }

            this.saleTotal = parseFloat(event.detail.total) || 0;
            this.calculateChangeAndBalance();
        });

        // --- 4. Sincronización Automática (Modal -> Hidden Inputs) ---
        Object.keys(this.fieldsToSync).forEach((modalId) => {
            const modalEl = document.getElementById(modalId);
            if (modalEl) {
                ["input", "change"].forEach((eventType) => {
                    modalEl.addEventListener(eventType, (e) => {
                        const hiddenEl = document.getElementById(
                            this.fieldsToSync[modalId]
                        );
                        if (hiddenEl) hiddenEl.value = e.target.value;
                    });
                });
            }
        });

        // Sincronización de Descuento
        const discountSelect = document.getElementById("discount_id");
        if (discountSelect) {
            discountSelect.addEventListener("change", (e) => {
                const hiddenDiscount =
                    document.getElementById("hidden_discount_id");
                if (hiddenDiscount) hiddenDiscount.value = e.target.value;
            });
        }
    },

    syncModalFields: function () {
        // Sincronizar inputs de texto/número/fecha
        Object.keys(this.fieldsToSync).forEach((modalId) => {
            const modalEl = document.getElementById(modalId);
            const hiddenEl = document.getElementById(
                this.fieldsToSync[modalId]
            );
            if (modalEl && hiddenEl) hiddenEl.value = modalEl.value;
        });

        // Sincronizar el select de pago
        const modalPaymentType = document.querySelector(
            'select[name="payment_type_modal"]'
        );
        const hiddenType = document.getElementById("hidden_payment_type");
        if (modalPaymentType && hiddenType)
            hiddenType.value = modalPaymentType.value;
    },

    detectSaleType: function () {
        const saleTypeSelect = document.querySelector(
            'select[name="sale_type"]'
        );
        if (!saleTypeSelect) return;

        this.saleType = saleTypeSelect.value;
        this.applySaleTypeUI();
    },

    // resources/js/modules/sales/partials/sale-payment.js

    handleSaleTypeChange: function () {
        const saleTypeSelect = document.querySelector(
            'select[name="sale_type"]'
        );
        if (!saleTypeSelect) return;

        this.saleType = saleTypeSelect.value;

        // Notificar siempre que el tipo cambie para limpiar la tabla de productos
        // Esto asegura que si pasas de Reparación -> Venta, la tabla también se limpie
        document.dispatchEvent(
            new CustomEvent("sale:typeChanged", {
                detail: { saleType: this.saleType },
            })
        );

        this.applySaleTypeUI();
        this.calculateChangeAndBalance();
    },
    // resources/js/modules/sales/partials/sale-payment.js

    applySaleTypeUI: function () {
        const saleTotalWrapper = document.getElementById("sale-total-wrapper");
        const repairWrapper = document.getElementById("repair-amount-wrapper");
        const repairInput = document.getElementById("repair_amount");
        const hiddenRepair = document.getElementById("hidden_repair_amount");
        const repairTypeWrapper = document.getElementById(
            "repair-type-wrapper"
        );

        const isRepair = this.saleType === SALE_TYPE.REPAIR;
        const repairTypeSelect = repairTypeWrapper?.querySelector("select");

        if (isRepair) {
            saleTotalWrapper?.classList.add("d-none");
            repairWrapper?.classList.remove("d-none");
            repairTypeWrapper?.classList.remove("d-none");

            if (repairInput) repairInput.disabled = false;
            if (hiddenRepair) hiddenRepair.disabled = false;
            if (repairTypeSelect) repairTypeSelect.disabled = false;

            this.setSaleTotalFromRepair();
        } else {
            // --- MODO VENTA ---
            repairWrapper?.classList.add("d-none");
            repairTypeWrapper?.classList.add("d-none");
            saleTotalWrapper?.classList.remove("d-none");

            if (repairInput) {
                repairInput.disabled = true;
                repairInput.value = "";
                repairInput.dispatchEvent(new Event("input"));
            }
            if (hiddenRepair) {
                hiddenRepair.disabled = true;
                hiddenRepair.value = "";
            }

            // LIMPIEZA DE SELECT (Choices.js)
            if (repairTypeSelect) {
                // Comprobamos si la instancia de Choices existe (guardada en x-init)
                if (repairTypeSelect._choices) {
                    // setChoiceByValue('') selecciona la opción con valor vacío (el placeholder)
                    repairTypeSelect._choices.setChoiceByValue("");
                } else {
                    repairTypeSelect.value = "";
                }

                repairTypeSelect.disabled = true;
                dispatchRepairCategoryChanged(null);
            }

            this.setSaleTotalFromSale();
        }
    },

    setSaleTotalFromRepair: function () {
        const repairInput = document.getElementById("repair_amount");
        const value = parseFloat(repairInput?.value) || 0;

        this.saleTotal = value;

        // Actualizar los SPAN del resumen directamente para asegurar consistencia
        const summarySubtotal = document.getElementById("summary_subtotal");
        const summaryTotal = document.getElementById("summary_total");

        if (summarySubtotal) summarySubtotal.textContent = value.toFixed(2);
        if (summaryTotal) summaryTotal.textContent = value.toFixed(2);

        // Notificar al resto del sistema
        document.dispatchEvent(
            new CustomEvent("sale:totalUpdated", {
                detail: { total: value, isRepair: true },
            })
        );

        this.calculateChangeAndBalance();
    },

    setSaleTotalFromSale: function () {
        const totalField = document.getElementById("total_amount");
        this.saleTotal = parseFloat(totalField?.value) || 0;
        this.calculateChangeAndBalance();
    },

    initializePaymentFields: function () {
        const totalField = document.getElementById("total_amount");
        if (totalField) {
            this.saleTotal = parseFloat(totalField.value) || 0;
        }

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

        const changeReturned = Math.max(0, amountReceived - this.saleTotal);
        changeReturnedInput.value = changeReturned.toFixed(2);

        const remainingBalance = Math.max(0, this.saleTotal - amountReceived);
        remainingBalanceInput.value = remainingBalance.toFixed(2);

        // Disparar manualmente el evento 'input' para que la sincronización hacia el Hidden funcione
        changeReturnedInput.dispatchEvent(new Event("input"));
        remainingBalanceInput.dispatchEvent(new Event("input"));

        this.updatePaymentStatusVisual(
            remainingBalance,
            changeReturned,
            amountReceived
        );
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

    dispatchPaymentUpdatedEvent: function (
        amountReceived,
        changeReturned,
        remainingBalance
    ) {
        const event = new CustomEvent("sale:paymentUpdated", {
            detail: {
                amountReceived,
                changeReturned,
                remainingBalance,
                saleTotal: this.saleTotal,
            },
        });
        document.dispatchEvent(event);
    },

    setSaleTotal: function (total) {
        this.saleTotal = total || 0;
        this.calculateChangeAndBalance();
    },
};

window.salePayment = salePayment;
export default salePayment;
