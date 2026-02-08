// resources/js/modules/sales/payment/PaymentMethodHandler.js

import { PAYMENT_TYPE, PAYMENT_MODELS } from "../constants/payment-constants";

export default class PaymentMethodHandler {
    constructor(domHelper, syncManager) {
        this.dom = domHelper;
        this.sync = syncManager;
    }

    /**
     * Actualiza la visibilidad de métodos de pago (tarjeta/transferencia)
     * según el tipo de pago seleccionado
     */
    updateMethodVisibility(paymentType, suffix = "") {
        const isCard = paymentType === PAYMENT_TYPE.CARD;
        const isTransfer = paymentType === PAYMENT_TYPE.TRANSFER;

        // Sincronizar grupo "Pago 1" (Vista e ID 1) o procesar individualmente el 2
        const updateGroups =
            suffix === "" || suffix === "1" ? ["", "1"] : [suffix];

        updateGroups.forEach((s) => {
            const isModal = s !== ""; // Is Modal?

            // IDs dinámicos según el sufijo
            const bankContainerId = isModal
                ? `container_bank_id_${s}_modal`
                : "container_payment_method_bank";
            const accountContainerId = isModal
                ? `container_bank_account_id_${s}_modal`
                : "container_payment_method_account";
            const bankSelectId = isModal
                ? `bank_id_${s}_modal`
                : "bank_id_visible";
            const accountSelectId = isModal
                ? `bank_account_id_${s}_modal`
                : "bank_account_id_visible";

            // 1. Toggle de contenedores
            this.dom.toggle(this.dom.el(`contB${s}`, bankContainerId), isCard);
            this.dom.toggle(
                this.dom.el(`contA${s}`, accountContainerId),
                isTransfer,
            );

            // 2. Obtención y reset de selects
            const bankSelect = this.dom.el(`bank${s}`, bankSelectId);
            const accountSelect = this.dom.el(`acc${s}`, accountSelectId);

            if (!isCard) this.sync.resetSelect(bankSelect);
            if (!isTransfer) this.sync.resetSelect(accountSelect);

            // 3. Polimorfismo
            this.updatePolymorphicType(paymentType, s);
        });
    }

    /**
     * Actualiza el tipo polimórfico del método de pago
     */
    updatePolymorphicType(paymentType, suffix = "") {
        // Normalizamos: "" y "1" afectan al primer pago, "2" al segundo
        const isSecond = suffix === "2";
        const cacheKey = isSecond ? "hType2" : "hType1";
        const elementId = isSecond
            ? "hidden_payment_method_type_2"
            : "hidden_payment_method_type";

        const hiddenType = this.dom.el(cacheKey, elementId);

        if (hiddenType) {
            hiddenType.value = PAYMENT_MODELS[paymentType] || "";
        }
    }

    /**
     * Obtiene el label legible del tipo de pago seleccionado
     */
    getPaymentTypeLabel(selectId) {
        const select = this.dom.el(selectId, selectId);
        if (!select) return "Método";

        try {
            // Obtener el texto de la opción seleccionada directamente del DOM
            const selectedOption = select.querySelector("option:checked");
            if (selectedOption) {
                return selectedOption.textContent.trim();
            }

            // Fallback: usar selectedIndex
            if (
                select.selectedIndex >= 0 &&
                select.options[select.selectedIndex]
            ) {
                return select.options[select.selectedIndex].textContent.trim();
            }

            return "Método";
        } catch (error) {
            console.warn(`Error obteniendo label de ${selectId}:`, error);
            return "Método";
        }
    }

    /**
     * Actualiza las etiquetas visuales de los tipos de pago en el resumen
     */
    updatePaymentTypeLabels() {
        const paymentType1 = this.getPaymentTypeLabel("payment_type_1_modal");
        const paymentType2 = this.getPaymentTypeLabel("payment_type_2_modal");

        this.dom.setText("summary_payment_type_1_label", paymentType1);
        this.dom.setText("summary_payment_type_2_label", paymentType2);
    }
}
