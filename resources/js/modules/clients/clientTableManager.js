import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// Configuración específica de clientes
const TABLE_CONFIG = {
    tableId: "clients-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id, full_name } = row.dataset;

                // Redirigir a la página de edición
                window.location.href = `/web/clients/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, full_name } = row.dataset;
                deleteItem(`/web/clients/${id}`, `el cliente "${full_name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: () => {
                window.location.href = "/web/clients/create";
            },
        },
    },
};

export function initClientTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initClientTable,
    config: TABLE_CONFIG,
};
