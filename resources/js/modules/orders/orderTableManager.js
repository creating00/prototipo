import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// 1. Extraemos la URL base del componente Blade (ej: /web/orders)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";

const TABLE_CONFIG = {
    tableId: "orders-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Redirección dinámica: /web/orders/{id}/edit
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;
                // Eliminación dinámica: /web/orders/{id}
                deleteItem(`${baseUrl}/${id}`, `la orden "${name || id}"`);
            },
        },
    },
    headerActions: {
        newClient: {
            selector: ".btn-header-new-client",
            handler: (baseUrl) => {
                // Ruta dinámica: /web/orders/create-client
                window.location.href = `${baseUrl}/create-client`;
            },
        },
        newBranch: {
            selector: ".btn-header-new-branch",
            handler: (baseUrl) => {
                // Ruta dinámica: /web/orders/create-branch
                window.location.href = `${baseUrl}/create-branch`;
            },
        },
    },
};

export function initOrderTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initOrderTable,
    config: TABLE_CONFIG,
};
