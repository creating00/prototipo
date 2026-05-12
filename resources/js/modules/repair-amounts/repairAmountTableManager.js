import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// 1. Extraemos la URL base del contenedor de la tabla (inyectada por el componente Blade)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";

const TABLE_CONFIG = {
    tableId: "repair-amounts-table",

    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;

                // Redirección dinámica usando la URL capturada del DOM
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },

        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;

                // Ruta de eliminación dinámica
                deleteItem(`${baseUrl}/${id}`, `el monto "${name}"`);
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

export function initRepairAmountTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initRepairAmountTable,
    config: TABLE_CONFIG,
};
