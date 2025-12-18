import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// Configuración específica para Sucursales
const TABLE_CONFIG = {
    tableId: "branches-table",

    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id } = row.dataset;
                window.location.href = `/web/branches/${id}/edit`;
            },
        },

        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, name } = row.dataset;
                deleteItem(`/web/branches/${id}`, `la sucursal "${name}"`);
            },
        },
    },

    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: () => {
                window.location.href = "/web/branches/create";
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
