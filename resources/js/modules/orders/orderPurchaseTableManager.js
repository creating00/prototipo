import { TableManager } from "../../components/TableManager";

// 1. Extraemos la URL base del componente Blade (ej: /web/orders)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";

const TABLE_CONFIG = {
    tableId: "purchases-table",
    rowActions: {
        view: {
            selector: ".btn-view",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                window.location.href = `${baseUrl}/${id}/details`;
            },
        },
        print: {
            selector: ".btn-print",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Abrir en pestaña nueva para imprimir
                window.open(`${baseUrl}/${id}/print`, '_blank');
            },
        },
    }
};

export function initOrderPurchaseTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initOrderPurchaseTable,
    config: TABLE_CONFIG,
};
