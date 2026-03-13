// resources/js/modules/sales/payment/PaymentManager.js

import PaymentCalculator from "../services/PaymentCalculator";
import { SALE_TYPE } from "../constants/payment-constants";

export default class PaymentManager {
    constructor(
        domHelper,
        stateManager,
        syncManager,
        uiUpdater,
        methodHandler,
    ) {
        this.dom = domHelper;
        this.state = stateManager;
        this.sync = syncManager;
        this.ui = uiUpdater;
        this.methodHandler = methodHandler;
    }

    /**
     * Calcula y actualiza todos los valores de pago
     */
    calculate() {
        if (this.state.isSyncing) return;

        this.state.withSync(() => {
            const isDollarMode = this.dom.el(
                "payDollars",
                "pay_in_dollars",
            )?.checked;
            const rate = this._getFloat("exchange_rate_blue") || 1;

            // 1. Obtener montos de los 3 nuevos inputs
            const cash = this._getFloat("amount_received_cash");
            const transfer = this._getFloat("amount_received_transfer");
            const card = this._getFloat("amount_received_card");

            this._handleInputsLock(cash, transfer, card);

            // 2. Sumar total recibido (asumimos que los inputs ya están en la moneda que indica el modo)
            let totalReceivedArs = cash + transfer + card;

            if (isDollarMode) {
                totalReceivedArs = totalReceivedArs * rate;
            }

            // 3. Operar con el calculador
            const result = PaymentCalculator.calculate(
                this.state.saleTotal,
                totalReceivedArs,
            );

            const changeArs = parseFloat(result.change) || 0;
            const balanceArs = parseFloat(result.balance) || 0;
            const symbol = isDollarMode ? "U$D" : "$";

            // 4. Conversiones para visualización
            const displayTotal = isDollarMode
                ? this.state.saleTotal / rate
                : this.state.saleTotal;
            const displayBalance = isDollarMode
                ? balanceArs / rate
                : balanceArs;
            const displayChange = isDollarMode ? changeArs / rate : changeArs;

            // 5. Actualizar UI
            this.ui.updateSummaryDisplay({
                displayTotal,
                displayBalance,
                displayChange,
                displayR1: 0, // Podemos enviar 0 o el total según pida tu UIUpdater
                displayR2: 0,
                totalReceivedArs,
                changeArs,
                balanceArs,
                isDualEnabled:
                    (cash > 0 && (transfer > 0 || card > 0)) ||
                    (transfer > 0 && card > 0),
                symbol,
            });

            // 6. Sincronizar con campos Hidden para el Backend
            this._syncHiddensForBackend(cash, transfer, card);

            // 7. Actualizar campos ocultos de totales y badges
            this.ui.updateHiddenFields(displayChange, displayBalance);

            const status = PaymentCalculator.getStatus(
                this.state.saleTotal,
                totalReceivedArs,
                changeArs,
                balanceArs,
            );
            this.ui.updatePaymentStatusBadges(
                `<span class="badge bg-${status.class}">${status.label}</span>`,
            );
        });
    }

    /**
     * Mapea los montos a la estructura que espera el backend
     */
    _syncHiddensForBackend(cash, transfer, card) {
        const isDollarMode = this.dom.el(
            "payDollars",
            "pay_in_dollars",
        )?.checked;
        const rate = this._getFloat("exchange_rate_blue") || 1;

        // 1. Calcular el Total según la Moneda para 'hidden_totals'
        // El backend espera: {"1": total_ars} o {"2": total_usd}
        const totalReceived = cash + transfer + card;
        const totalsData = {};

        if (isDollarMode) {
            // Llave 2 = USD (según tu indicación de moneda)
            totalsData[2] = totalReceived;
        } else {
            // Llave 1 = ARS
            totalsData[1] = totalReceived;
        }

        const totalsHidden = document.getElementById("hidden_totals");
        if (totalsHidden) {
            totalsHidden.value = JSON.stringify(totalsData);
        }

        // 2. Mantener la lógica de pagos individuales para el desglose (Legacy)
        const legacyData = {
            cash: { amount: cash, type: 1 },
            card: {
                amount: card,
                type: 2,
                id: this.dom.el("bank_id_card", "bank_id_card")?.value,
                model: "App\\Models\\Bank",
            },
            transfer: {
                amount: transfer,
                type: 3,
                id: this.dom.el(
                    "bank_account_id_transfer",
                    "bank_account_id_transfer",
                )?.value,
                model: "App\\Models\\BankAccount",
            },
        };

        this._fillLegacyHiddens(legacyData);
    }

    _fillLegacyHiddens(data) {
        // Filtramos solo los que tienen monto > 0
        const active = Object.values(data).filter((p) => p.amount > 0);

        // Pago 1
        if (active[0]) {
            this._setVal("hidden_payment_type", active[0].type);
            this._setVal("hidden_amount_received", active[0].amount);
            this._setVal("hidden_payment_method_id", active[0].id || "");
            this._setVal("hidden_payment_method_type", active[0].model || "");
        }

        // Pago 2
        if (active[1]) {
            this._setVal("hidden_payment_type_2", active[1].type);
            this._setVal("hidden_amount_received_2", active[1].amount);
            this._setVal("hidden_payment_method_id_2", active[1].id || "");
            this._setVal("hidden_payment_method_type_2", active[1].model || "");
            this._setVal("hidden_enable_dual_payment", 1);
        } else {
            this._setVal("hidden_enable_dual_payment", 0);
            this._setVal("hidden_amount_received_2", 0);
        }
    }

    _setVal(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val;
    }

    /**
     * Aplica un descuento manual al total
     */
    applyManualDiscount() {
        if (this.state.isSyncing) return;

        const subtotal = this._getFloat("subtotal_amount");
        const discount = this._getFloat("discount_amount_input");

        const newTotal = Math.max(0, subtotal - discount);
        this.state.saleTotal = newTotal;

        // Actualizar display del total
        this.ui.updateTotalDisplay(newTotal);

        // Sincronizar repair amount
        this.sync.syncRepairAmount();

        // Actualizar totales hidden
        this.updateTotalsHiddenFields(newTotal);

        // Recalcular
        this.calculate();

        // Dispatch event
        this._dispatchEvent("sale:discountUpdated", { discount });
    }

    /**
     * Establece el total de la venta
     */
    setTotal(value) {
        if (this.state.isSyncing) return;

        this.state.withSync(() => {
            this.state.saleTotal = parseFloat(value) || 0;
            this.sync.syncRepairAmount();
            this.updateTotalsHiddenFields(this.state.saleTotal);
        });

        this.calculate();
        this._dispatchEvent("sale:totalUpdated", {
            total: this.state.saleTotal,
        });
    }

    /**
     * Actualiza los campos ocultos de totales
     */
    updateTotalsHiddenFields(total) {
        const rate = this._getFloat("exchange_rate_blue") || 1;
        const isDollarMode = this.dom.el(
            "payDollars",
            "pay_in_dollars",
        )?.checked;

        this.ui.updateTotalsHiddenFields(total, rate, isDollarMode, () =>
            this.calculate(),
        );
    }

    /**
     * Refresca el total desde la fuente (repair o sale)
     */
    refreshTotalFromSource() {
        const source =
            this.state.saleType === SALE_TYPE.REPAIR
                ? this.dom.el("repairAmt", "repair_amount")
                : this.dom.el("totalAmt", "total_amount");

        this.setTotal(source?.value || 0);
    }

    /**
     * Maneja el cambio de tipo de venta
     */
    handleSaleTypeChange(value, RepairUiManager) {
        this.state.saleType = value;
        this._dispatchEvent("sale:typeChanged", { saleType: value });
        RepairUiManager.toggleFields(value === SALE_TYPE.REPAIR);
        this.refreshTotalFromSource();
    }

    // Helpers privados
    _getFloat(id) {
        const el = this.dom.el(id, id);
        return parseFloat(el?.value) || 0;
    }

    _dispatchEvent(eventName, detail) {
        document.dispatchEvent(new CustomEvent(eventName, { detail }));
    }

    /**
     * Bloquea el tercer input si ya hay dos con valores
     */
    _handleInputsLock(cash, transfer, card) {
        const inputs = [
            { id: "amount_received_cash", val: cash },
            { id: "amount_received_transfer", val: transfer },
            { id: "amount_received_card", val: card },
        ];

        const activeInputs = inputs.filter((i) => i.val > 0);

        if (activeInputs.length >= 2) {
            // Bloquear los que están en 0
            inputs.forEach((i) => {
                const el = document.getElementById(i.id);
                if (i.val === 0 && el) {
                    el.readOnly = true;
                    el.parentElement.classList.add("bg-light"); // Feedback visual
                    el.placeholder = "Máximo 2 métodos";
                }
            });
        } else {
            // Desbloquear todos
            inputs.forEach((i) => {
                const el = document.getElementById(i.id);
                if (el) {
                    el.readOnly = false;
                    el.parentElement.classList.remove("bg-light");
                    el.placeholder = "Monto";
                }
            });
        }
    }
}
