// resources/js/modules/sales/services/TableUiManager.js
const TableUiManager = {
    updateIndices: (tableBody) => {
        if (!tableBody) return;
        tableBody.querySelectorAll("tr").forEach((row, index) => {
            row.dataset.index = index;
            row.querySelectorAll("input, select").forEach((input) => {
                if (input.name) {
                    input.name = input.name.replace(
                        /items\[(\d+|INDEX)\]/,
                        `items[${index}]`
                    );
                }
            });
        });
    },

    togglePriceLock: (row, status) => {
        const btn = row.querySelector(".btn-edit-price");
        const icon = btn?.querySelector("i");
        const input = row.querySelector(".unit-price");

        if (status === "on") {
            input.removeAttribute("readonly");
            input.classList.add("bg-white");
            btn.classList.replace("btn-outline-warning", "btn-warning");
            icon.classList.replace("fa-lock", "fa-lock-open");
        } else {
            input.setAttribute("readonly", true);
            input.classList.remove(
                "bg-white",
                "is-invalid",
                "border-danger",
                "border-warning"
            );
            btn.classList.replace("btn-warning", "btn-outline-warning");
            icon.classList.replace("fa-lock-open", "fa-lock");
        }
    },
};

export default TableUiManager;
