import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// bankaccount-accounts/index.js
const TABLE_CONFIG = {
    tableId: "bank-accounts-table",
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
                deleteItem(`${baseUrl}/${id}`, `la cuenta "${name}"`);
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

export function initBankAccountTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initBankAccountTable,
    config: TABLE_CONFIG,
};
