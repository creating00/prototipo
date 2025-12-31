import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

const tableContainer = document.querySelector("[data-provider-order-url]");

const provicerOrderUrl = tableContainer ? tableContainer.dataset.providerOrderUrl : "";

const TABLE_CONFIG = {
    tableId: "providers-table",
    rowActions: {
        view: {
            selector: ".btn-view",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Redirección dinámica: /web/orders/{id}/details
                window.location.href = `${baseUrl}/${id}`;
            },
        },
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;

                // Redirección dinámica usando la URL capturada
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;

                // Eliminación dinámica con mensaje corregido
                deleteItem(`${baseUrl}/${id}`, `el proveedor "${name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: (baseUrl) => {
                // Ruta de creación dinámica
                window.location.href = `${baseUrl}/create`;
            },
        },
        newProviderOrder: {
            selector: ".btn-header-new-order-provider",
            handler: (baseUrl) => {
                // Ruta dinámica: /web/orders/create-client
                window.location.href = `${provicerOrderUrl}/create`;
            },
        },
    },
};

export function initProviderTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initProviderTable,
    config: TABLE_CONFIG,
};
