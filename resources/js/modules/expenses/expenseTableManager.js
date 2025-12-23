import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// 1. Extraemos la URL base del componente Blade (ej: /web/expenses)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";

const TABLE_CONFIG = {
    tableId: "expenses-table",

    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Redirección dinámica
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },

        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, description } = row.dataset;
                // Usamos description o el campo que tengas mapeado en el Service
                deleteItem(
                    `${baseUrl}/${id}`,
                    `el gasto "${description || id}"`
                );
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

export function initExpenseTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initExpenseTable,
    config: TABLE_CONFIG,
};
