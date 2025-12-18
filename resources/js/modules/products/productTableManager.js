import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// Configuración específica de productos
const TABLE_CONFIG = {
    tableId: "products-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id, full_name } = row.dataset;

                // Redirigir a la página de edición
                window.location.href = `/web/products/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, full_name } = row.dataset;
                deleteItem(`/web/products/${id}`, `el cliente "${full_name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: () => {
                window.location.href = "/web/products/create";
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
