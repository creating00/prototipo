// resources/js/modules/sales/services/RepairUiManager.js
import { dispatchRepairCategoryChanged } from "@/helpers/repair-category-events";

const RepairUiManager = {
    toggleFields: function (isRepair) {
        const wrappers = {
            total: document.getElementById("sale-total-wrapper"),
            repair: document.getElementById("repair-amount-wrapper"),
            type: document.getElementById("repair-type-wrapper"),
        };

        const repairInput = document.getElementById("repair_amount");
        const hiddenRepair = document.getElementById("hidden_repair_amount");
        const repairTypeRadios = wrappers.type?.querySelectorAll('input[name="repair_type_id"]');
        const repairTypeSelect = wrappers.type?.querySelector("select");

        // UI toggles
        wrappers.total?.classList.toggle("d-none", isRepair);
        wrappers.repair?.classList.toggle("d-none", !isRepair);
        wrappers.type?.classList.toggle("d-none", !isRepair);

        if (isRepair) {
            this.enableRepair(repairInput, hiddenRepair, repairTypeSelect, repairTypeRadios);
        } else {
            this.disableRepair(repairInput, hiddenRepair, repairTypeSelect, repairTypeRadios);
        }
    },

    enableRepair: function (input, hidden, select, radios) {
        [input, hidden, select].forEach((el) => {
            if (el) el.disabled = false;
        });
        if (radios) {
            radios.forEach((r) => {
                r.disabled = false;
            });
        }
        if (input) input.readOnly = true; // El total ahora viene de la tabla

        const checkedRadio = document.querySelector('input[name="repair_type_id"]:checked');
        const currentType = checkedRadio?.value || select?.value || null;
        dispatchRepairCategoryChanged(currentType);
    },

    disableRepair: function (input, hidden, select, radios) {
        if (input) {
            input.disabled = true;
            input.readOnly = false;
            input.value = "";
            input.dispatchEvent(new Event("input"));
        }
        if (hidden) {
            hidden.disabled = true;
            hidden.value = "";
        }
        if (select) {
            if (select._choices) select._choices.setChoiceByValue("");
            else select.value = "";
            select.disabled = true;
        }
        if (radios) {
            radios.forEach((r) => {
                r.checked = false;
                r.disabled = true;
            });
        }
        dispatchRepairCategoryChanged(null);
    },
};

export default RepairUiManager;
