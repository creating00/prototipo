import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";
import BootstrapSwal from "../../config/sweetalert";

// 1. Extraemos la URL base del componente Blade (ej: /web/orders)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";
const apiUrl = tableContainer ? tableContainer.dataset.apiUrl : "/api/orders";

const TABLE_CONFIG = {
    tableId: "provider-orders-table",
    rowActions: {
        view: {
            selector: ".btn-view",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Redirección dinámica: /web/orders/{id}/details
                window.location.href = `${baseUrl}/${id}/details`;
            },
        },
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Redirección dinámica: /web/orders/{id}/edit
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;
                // Eliminación dinámica: /web/orders/{id}
                deleteItem(`${baseUrl}/${id}`, `la orden "${name || id}"`);
            },
        },
        receive: {
            selector: ".btn-receive",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                const orderNumber =
                    row.querySelector("td:nth-child(2)").textContent;

                // Usamos el nuevo helper que creamos
                BootstrapSwal.confirmReceive(orderNumber).then((result) => {
                    if (result.isConfirmed) {
                        // Lógica del formulario POST
                        const form = document.createElement("form");
                        form.method = "POST";
                        form.action = `${baseUrl}/${id}/receive`;

                        const csrfToken = document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content");
                        const csrfInput = document.createElement("input");
                        csrfInput.type = "hidden";
                        csrfInput.name = "_token";
                        csrfInput.value = csrfToken;

                        form.appendChild(csrfInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            },
        },
    },
    headerActions: {
        newClient: {
            selector: ".btn-header-new-provider-order",
            handler: (baseUrl) => {
                // Ruta dinámica: /web/orders/create-client
                window.location.href = `${baseUrl}/create`;
            },
        },
    },
};

export function initOrderTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initOrderTable,
    config: TABLE_CONFIG,
};
