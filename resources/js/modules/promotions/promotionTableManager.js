import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

const TABLE_CONFIG = {
    tableId: "promotions-table",
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
                const { id, title } = row.dataset;
                deleteItem(`${baseUrl}/${id}`, `la promoción "${title}"`);
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

export function initPromotionTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initPromotionTable,
    config: TABLE_CONFIG,
};
