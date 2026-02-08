// resources/js/modules/sales/payment/PaymentSyncManager.js

import { SALE_TYPE } from "../constants/payment-constants";

export default class PaymentSyncManager {
    constructor(domHelper, stateManager) {
        this.dom = domHelper;
        this.state = stateManager;
    }

    /**
     * Vincula sincronización bidireccional entre dos elementos
     */
    bindSync(source, target, eventType, isSelect = false) {
        if (!source || !target) return;

        const syncFn = isSelect ? this.syncSelects : this.syncFields;

        source.addEventListener(eventType, (e) =>
            syncFn.call(this, e.target.value, target),
        );
        target.addEventListener(eventType, (e) =>
            syncFn.call(this, e.target.value, source),
        );
    }

    /**
     * Sincroniza campos de texto/input
     */
    syncFields(value, targetEl) {
        if (this.state.isSyncing || !targetEl || targetEl.value === value) {
            return;
        }

        this.state.withSync(() => {
            targetEl.value = value;
            targetEl.dispatchEvent(new Event("input"));
        });
    }

    /**
     * Sincroniza elementos select (con soporte para Choices.js)
     */
    syncSelects(value, targetEl) {
        if (this.state.isSyncing || !targetEl || targetEl.value === value) {
            return;
        }

        this.state.withSync(() => {
            targetEl.value = value;
            if (targetEl._choices) {
                targetEl._choices.setChoiceByValue(value);
            }
            targetEl.dispatchEvent(new Event("change"));
        });
    }

    /**
     * Sincroniza el monto de reparación según el tipo de venta
     */
    syncRepairAmount() {
        const val = this.state.saleTotal.toFixed(2);
        const targetValue = this.state.saleType === SALE_TYPE.REPAIR ? val : "";

        [
            this.dom.el("repairAmt", "repair_amount"),
            this.dom.el("hiddenRepair", "hidden_repair_amount"),
        ]
            .filter(Boolean)
            .forEach((input) => {
                if (input.value !== targetValue) {
                    input.value = targetValue;
                    input.dispatchEvent(new Event("input"));
                }
            });
    }

    /**
     * Resetea un select a su valor vacío
     */
    resetSelect(selectEl) {
        if (!selectEl) return;

        this.state.withSync(() => {
            selectEl.value = "";
            if (selectEl._choices) {
                selectEl._choices.setChoiceByValue("");
            }
            selectEl.dispatchEvent(new Event("change"));
        });
    }
}
