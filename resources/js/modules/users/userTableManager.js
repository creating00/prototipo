import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

const TABLE_CONFIG = {
    tableId: "users-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Redirección automática: /admin/users/{id}/edit
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;
                // Llamada al helper de eliminación con SweetAlert2
                deleteItem(`${baseUrl}/${id}`, `al usuario "${name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: (baseUrl) => {
                window.location.href = `${baseUrl}/create`;
            },
        },
    },
};

/**
 * Inicializa la tabla de usuarios
 */
export function initUserTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

// Ejecución inmediata para Vite
document.addEventListener("DOMContentLoaded", () => {
    initUserTable();
});

export default {
    init: initUserTable,
    config: TABLE_CONFIG,
};
