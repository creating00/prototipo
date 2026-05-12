import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// banks/index.js
const TABLE_CONFIG = {
    tableId: "banks-table",
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
                deleteItem(`${baseUrl}/${id}`, `el banco "${name}"`);
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

export function initBankTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initBankTable,
    config: TABLE_CONFIG,
};
