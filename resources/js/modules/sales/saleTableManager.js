import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

const TABLE_CONFIG = {
    tableId: "sales-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset; // name o el campo que uses para mostrar
                deleteItem(`${baseUrl}/${id}`, `la venta de "${name || id}"`);
            },
        },
    },
    headerActions: {
        newClient: {
            selector: ".btn-header-new-client",
            handler: (baseUrl) => {
                window.location.href = `${baseUrl}/create-client`;
            },
        },
        newBranch: {
            selector: ".btn-header-new-branch",
            handler: (baseUrl) => {
                window.location.href = `${baseUrl}/create-branch`;
            },
        },
    },
};

export function initSalesTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initSalesTable,
    config: TABLE_CONFIG,
};
