import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// categories/index.js
const TABLE_CONFIG = {
    tableId: "categories-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                window.location.href = `${baseUrl}/${row.dataset.id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;
                deleteItem(`${baseUrl}/${id}`, `la categoría "${name}"`);
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

export function initCategoryTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initCategoryTable,
    config: TABLE_CONFIG,
};
