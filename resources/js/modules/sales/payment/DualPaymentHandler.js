// resources/js/modules/sales/payment/DualPaymentHandler.js

export default class DualPaymentHandler {
    constructor(domHelper, methodHandler, calculateCallback) {
        this.dom = domHelper;
        this.methodHandler = methodHandler;
        this.calculateCallback = calculateCallback;
    }

    /**
     * Activa/desactiva el modo de pago doble
     */
    toggleDualPayment(enabled) {
        // Actualizar campo oculto
        const hiddenDual = this.dom.el(
            "hiddenDual",
            "hidden_enable_dual_payment",
        );
        if (hiddenDual) {
            hiddenDual.value = enabled ? "1" : "0";
        }

        // Toggle de wrappers de UI
        this.dom.toggle(
            this.dom.el("wrapSingle", "wrapper_single_payment"),
            !enabled,
        );
        this.dom.toggle(
            this.dom.el("wrapDual", "wrapper_dual_payment_info"),
            enabled,
        );

        // Hacer inputs de montos readonly/editable
        [
            this.dom.el("modal1", "amount_received_1_modal"),
            this.dom.el("modal2", "amount_received_2_modal"),
        ]
            .filter(Boolean)
            .forEach((input) => {
                input.readOnly = !enabled;
            });

        // Habilitar/deshabilitar selects de métodos de pago
        this._togglePaymentSelects(enabled);

        // Resetear monto del segundo pago si se desactiva dual
        if (!enabled) {
            const modal2 = this.dom.el("modal2", "amount_received_2_modal");
            if (modal2) {
                modal2.value = "0.00";
                this.calculateCallback();
            }
        }

        // Actualizar etiquetas si se activa dual
        if (enabled) {
            this.methodHandler.updatePaymentTypeLabels();
        }
    }

    /**
     * Habilita o deshabilita los selects de métodos de pago
     * @private
     */
    _togglePaymentSelects(enabled) {
        const selectKeys = [
            "modalType1",
            "modalType2",
            "modalBank1",
            "modalBank2",
            "modalAcc1",
            "modalAcc2",
        ];

        selectKeys
            .map((key) => {
                // Convertir cacheKey a ID real
                // modalType1 -> payment_type_1_modal
                // modalBank1 -> bank_id_1_modal
                // modalAcc1 -> bank_account_id_1_modal
                const idMap = {
                    modalType1: "payment_type_1_modal",
                    modalType2: "payment_type_2_modal",
                    modalBank1: "bank_id_1_modal",
                    modalBank2: "bank_id_2_modal",
                    modalAcc1: "bank_account_id_1_modal",
                    modalAcc2: "bank_account_id_2_modal",
                };
                return this.dom.el(key, idMap[key]);
            })
            .filter(Boolean)
            .forEach((select) => {
                if (select._choices) {
                    enabled
                        ? select._choices.enable()
                        : select._choices.disable();
                } else {
                    select.disabled = !enabled;
                }
            });
    }

    /**
     * Verifica si el modo dual está activo y actualiza labels si es necesario
     */
    checkDualLabels() {
        const isDual = this.dom.el("dualCheck", "enable_dual_payment")?.checked;
        if (isDual) {
            this.methodHandler.updatePaymentTypeLabels();
        }
    }
}
