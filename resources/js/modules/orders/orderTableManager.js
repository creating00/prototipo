import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

// 1. Extraemos la URL base del componente Blade (ej: /web/orders)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";
const apiUrl = tableContainer ? tableContainer.dataset.apiUrl : "/api/orders";

const TABLE_CONFIG = {
    tableId: "orders-table",
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
        convert: {
            selector: ".btn-convert",
            handler: (row, baseUrl) => {
                // 1. Extraer datos del dataset (inyectados en el controlador)
                // Nota: dataset convierte data-total-amount -> totalAmount
                const { id, totalAmount, customerType } = row.dataset;

                // 2. Referencias a los elementos del modal
                const modalElement =
                    document.getElementById("convertOrderModal");
                const btnSave = document.getElementById("btnConfirmConvert");
                const displayId = document.getElementById("display_order_id");
                const inputAmount = document.getElementById(
                    "convert_amount_received"
                );
                const selectPayment = document.getElementById(
                    "convert_payment_type"
                );

                // 3. Rellenar la información visual y campos
                if (displayId) displayId.textContent = id;
                if (inputAmount) inputAmount.value = totalAmount;

                // 4. Lógica de selección automática de pago
                if (selectPayment) {
                    // Si el string contiene "Branch", ponemos 3 (Transferencia), si no 1 (Efectivo)
                    const isBranch =
                        customerType && customerType.includes("Branch");
                    selectPayment.value = isBranch ? "3" : "1";
                }

                // 5. Configurar la ruta de la API (apiUrl debe estar definida arriba en tu archivo)
                if (btnSave) {
                    // Usamos apiUrl para que el post vaya al controlador de la API
                    btnSave.setAttribute(
                        "data-route",
                        `${apiUrl}/${id}/convert`
                    );
                }

                // 6. Mostrar el modal
                const modalInstance =
                    bootstrap.Modal.getOrCreateInstance(modalElement);
                modalInstance.show();
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
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initOrderTable,
    config: TABLE_CONFIG,
};
