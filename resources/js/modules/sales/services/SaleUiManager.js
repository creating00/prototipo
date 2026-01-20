// resources/js/modules/sales/services/SaleUiManager.js
const SaleUiManager = {
    toggleRepairFields: (isRepair) => {
        const containers = {
            total: document.getElementById("sale-total-wrapper"),
            repair: document.getElementById("repair-amount-wrapper"),
            type: document.getElementById("repair-type-wrapper"),
        };

        const inputs = {
            amount: document.getElementById("repair_amount"),
            hidden: document.getElementById("hidden_repair_amount"),
            select: document
                .getElementById("repair-type-wrapper")
                ?.querySelector("select"),
        };

        // Visibilidad
        containers.total?.classList.toggle("d-none", isRepair);
        containers.repair?.classList.toggle("d-none", !isRepair);
        containers.type?.classList.toggle("d-none", !isRepair);

        // Estado de inputs
        Object.values(inputs).forEach((el) => {
            if (el) el.disabled = !isRepair;
        });
    },

    updateSummary: (value) => {
        const subtotal = document.getElementById("summary_subtotal");
        const total = document.getElementById("summary_total");
        if (subtotal) subtotal.textContent = value;
        if (total) total.textContent = value;
    },
};

export default SaleUiManager;
