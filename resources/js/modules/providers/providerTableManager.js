import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

const TABLE_CONFIG = {
    tableId: "providers-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;

                // Redirección dinámica usando la URL capturada
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;

                // Eliminación dinámica con mensaje corregido
                deleteItem(`${baseUrl}/${id}`, `el proveedor "${name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: (baseUrl) => {
                // Ruta de creación dinámica
                window.location.href = `${baseUrl}/create`;
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
