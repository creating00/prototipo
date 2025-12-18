import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// Configuración específica de categorías
const TABLE_CONFIG = {
    tableId: "providers-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id, name } = row.dataset;

                // Redirigir a la página de edición
                window.location.href = `/web/providers/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, name } = row.dataset;
                deleteItem(`/web/providers/${id}`, `la categoría "${name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: () => {
                window.location.href = "/web/providers/create";
            },
        },
    },
};

export function initProviderTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initProviderTable,
    config: TABLE_CONFIG,
};
