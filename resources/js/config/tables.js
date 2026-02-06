import { PRODUCT_MODAL_CONFIG } from "./datatables/product-modal-config.js";
import {
    setupSalesFilters,
    updateSalesFooter,
    setupExpenseFilters,
    updateExpenseFooter,
} from "./dt-filters";

export const TABLE_CONFIGS = {
    MAIN: {
        selector: ".datatable-main",
        options: {
            pageLength: 10,
            ordering: true,
        },
    },

    SMALL: {
        selector: ".datatable-sm",
        options: {
            pageLength: 5,
            ordering: false,
        },
    },

    SALES: {
        selector: ".datatable-sm-sales",
        options: {
            pageLength: 10,
            ordering: true,
            columnDefs: [
                {
                    targets: [3, 4],
                    render: function (data, type) {
                        if (type === "filter") {
                            const matches = [
                                ...data.matchAll(/data-search="([^"]+)"/g),
                            ];
                            return matches.length > 0
                                ? matches.map((m) => m[1]).join(" ")
                                : data;
                        }
                        return data;
                    },
                },
                { targets: "_all", className: "dt-center" },
            ],
            initComplete: function () {
                const api = this.api();
                setupSalesFilters(api);
                updateSalesFooter(api);
            },
            drawCallback: function () {
                updateSalesFooter(this.api());
            },
        },
    },

    EXPENSES: {
        selector: ".datatable-sm-expenses",
        options: {
            pageLength: 10,
            ordering: true,
            columnDefs: [
                {
                    targets: [5],
                    render: function (data, type, row, meta) {
                        if (type === "filter") {
                            // Extraemos el valor del atributo data-payment_type_raw si existe
                            const rowNode = meta.settings.aoData[meta.row].nTr;
                            return (
                                rowNode.getAttribute("data-payment_type_raw") ||
                                data
                            );
                        }
                        return data;
                    },
                },
                { targets: "_all", className: "dt-center" },
            ],
            initComplete: function () {
                const api = this.api();
                setupExpenseFilters(api);
                updateExpenseFooter(api);
            },
            drawCallback: function () {
                updateExpenseFooter(this.api());
            },
        },
    },

    PRODUCT_MODAL: PRODUCT_MODAL_CONFIG,
};
