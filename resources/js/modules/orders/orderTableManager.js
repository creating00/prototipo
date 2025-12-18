import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// Configuración específica de categorías
const TABLE_CONFIG = {
    tableId: "orders-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id, name } = row.dataset;
                console.log("Editando categoría:", row.dataset);

                // Redirigir a la página de edición
                window.location.href = `/web/orders/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, name } = row.dataset;
                deleteItem(`/web/orders/${id}`, `la categoría "${name}"`);
            },
        },
    },
    headerActions: {
        newClient: {
            selector: ".btn-header-new-client",
            handler: () => {
                window.location.href = "/web/orders/create-client";
            },
        },
        newBranch: {
            selector: ".btn-header-new-branch",
            handler: () => {
                window.location.href = "/web/orders/create-branch";
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
