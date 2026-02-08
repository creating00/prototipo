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
            const isDualEnabled = this.dom.el(
                "dualCheck",
                "enable_dual_payment",
            )?.checked;

            // 1. Obtener montos recibidos
            let r1 = this._getFloat("amount_received_1_modal");
            let r2 = this._getFloat("amount_received_2_modal");

            // Solo convertir a pesos si NO es modo dual
            // En modo dual, los valores modales YA están en pesos
            if (isDollarMode && !isDualEnabled) {
                r1 = r1 * rate;
            }

            const totalReceivedArs = r1 + r2;

            // 2. El calculador siempre opera en Pesos (Moneda Base)
            const result = PaymentCalculator.calculate(
                this.state.saleTotal,
                totalReceivedArs,
            );

            const changeArs = parseFloat(result.change) || 0;
            const balanceArs = parseFloat(result.balance) || 0;
            const symbol = isDollarMode ? "U$D" : "$";

            // 3. Conversión para visualización
            const displayTotal = isDollarMode
                ? this.state.saleTotal / rate
                : this.state.saleTotal;
            const displayBalance = isDollarMode
                ? balanceArs / rate
                : balanceArs;
            const displayChange = isDollarMode ? changeArs / rate : changeArs;
            const displayR1 = isDollarMode ? r1 / rate : r1;
            const displayR2 = isDollarMode ? r2 / rate : r2;

            // 4. Actualizar UI con todos los datos calculados
            this.ui.updateSummaryDisplay({
                displayTotal,
                displayBalance,
                displayChange,
                displayR1,
                displayR2,
                totalReceivedArs,
                changeArs,
                balanceArs,
                isDualEnabled,
                symbol,
            });

            // 5. Actualizar etiquetas de tipo de pago si es dual
            if (isDualEnabled) {
                this.methodHandler.updatePaymentTypeLabels();
            }

            // 6. Actualizar campos ocultos
            this.ui.updateHiddenFields(displayChange, displayBalance);

            // 7. Actualizar badges de estado
            const status = PaymentCalculator.getStatus(
                this.state.saleTotal,
                totalReceivedArs,
                changeArs,
                balanceArs,
            );
            const badgeHtml = `<span class="badge bg-${status.class}">${status.label}</span>`;
            this.ui.updatePaymentStatusBadges(badgeHtml);
        });
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
}
