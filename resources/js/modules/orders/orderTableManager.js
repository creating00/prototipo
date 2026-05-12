import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";
import { handleOrderConversion, handleOrderPrint, resetOrderConvertModal } from "./orderActions";

// 1. Extraemos la URL base del componente Blade (ej: /web/orders)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";
const saleUrl = tableContainer ? tableContainer.dataset.saleUrl : "";
const apiUrl = tableContainer ? tableContainer.dataset.apiUrl : "/api/orders";

let paymentManager = null;

const TABLE_CONFIG = {
    tableId: "orders-table",
    rowActions: {
        print: {
            selector: ".btn-print",
            handler: (row) => handleOrderPrint(row, saleUrl),
        },
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
        convert: {
            selector: ".btn-convert",
            handler: (row, baseUrl) => {
                handleOrderConversion(row, baseUrl);
            },
        },

        whatsapp: {
            selector: ".btn-whatsapp",
            handler: (row) => {
                // El navegador convierte data-whatsapp-url en whatsappUrl
                const { whatsappUrl } = row.dataset;

                if (
                    whatsappUrl &&
                    whatsappUrl !== "null" &&
                    whatsappUrl !== ""
                ) {
                    window.open(whatsappUrl, "_blank");
                } else {
                    Swal.fire({
                        icon: "info",
                        title: "Sin contacto",
                        text: "No se puede iniciar la conversación porque el cliente no tiene un teléfono válido.",
                        confirmButtonColor: "#28a745",
                    });
                }
            },
        },
    },
    headerActions: {
        newClient: {
            selector: ".btn-header-new-client",
            handler: (baseUrl) => {
                // Ruta dinámica: /web/orders/create-client
                window.location.href = `${baseUrl}/create-client`;
            },
        },
        histoyPurchase: {
            selector: ".btn-header-history-purchase",
            handler: (baseUrl) => {
                // Ruta dinámica: /web/orders/purchases
                window.location.href = `${baseUrl}/purchases`;
            },
        },
        newBranch: {
            selector: ".btn-header-new-branch",
            handler: (baseUrl) => {
                // Ruta dinámica: /web/orders/create-branch
                window.location.href = `${baseUrl}/create-branch`;
            },
        },
    },
};

export function initOrderTable() {
    const modalElement = document.getElementById("convertOrderModal");

    if (modalElement && !modalElement.dataset.listenerAttached) {
        modalElement.addEventListener("hidden.bs.modal", () => {
            // Llamamos al reset del módulo
            resetOrderConvertModal();
        });

        modalElement.dataset.listenerAttached = "true";
    }

    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initOrderTable,
    config: TABLE_CONFIG,
};
