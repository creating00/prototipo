import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

const TABLE_CONFIG = {
    tableId: "products-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Ahora es automático: /web/products + / + id + /edit
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;
                // /web/products + / + id
                deleteItem(`${baseUrl}/${id}`, `el producto "${name}"`);
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

export function initProductTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initProductTable,
    config: TABLE_CONFIG,
};
