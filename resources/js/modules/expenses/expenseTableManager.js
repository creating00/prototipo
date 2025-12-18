import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// Configuración específica para Gastos
const TABLE_CONFIG = {
    tableId: "expenses-table",

    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id } = row.dataset;
                window.location.href = `/web/expenses/${id}/edit`;
            },
        },

        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, name } = row.dataset;
                deleteItem(`/web/expenses/${id}`, `la sucursal "${name}"`);
            },
        },
    },

    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: () => {
                window.location.href = "/web/expenses/create";
            },
        },
    },
};

export function initBranchTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initBranchTable,
    config: TABLE_CONFIG,
};
