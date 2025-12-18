import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// Configuración específica de categorías
const TABLE_CONFIG = {
    tableId: "categories-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id, name } = row.dataset;
                console.log("Editando categoría:", row.dataset);

                // Redirigir a la página de edición
                window.location.href = `/web/categories/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, name } = row.dataset;
                deleteItem(`/web/categories/${id}`, `la categoría "${name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: () => {
                window.location.href = "/web/categories/create";
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
