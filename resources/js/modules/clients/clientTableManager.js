import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// 1. Extraemos la URL base (ej: /web/clients) desde el componente Blade
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";

const TABLE_CONFIG = {
    tableId: "clients-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;

                // Redirigir dinámicamente usando la base URL capturada
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, full_name } = row.dataset;

                // Eliminación dinámica
                deleteItem(`${baseUrl}/${id}`, `el cliente "${full_name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: (baseUrl) => {
                // Creación dinámica
                window.location.href = `${baseUrl}/create`;
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
