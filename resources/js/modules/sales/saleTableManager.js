import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// Configuración específica de categorías
const TABLE_CONFIG = {
    tableId: "sales-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id, name } = row.dataset;
                window.location.href = `/sales/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, name } = row.dataset;
                deleteItem(`/sales/${id}`, `la categoría "${name}"`);
            },
        },
    },
    headerActions: {
        newClient: {
            selector: ".btn-header-new-client",
            handler: () => {
                window.location.href = "/web/sales/create-client";
            },
        },
        newBranch: {
            selector: ".btn-header-new-branch",
            handler: () => {
                window.location.href = "/web/sales/create-branch";
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
