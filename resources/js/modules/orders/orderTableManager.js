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
            handler: (row) => {
                const { id, total, customer_type } = row.dataset;

                const modalElement =
                    document.getElementById("convertOrderModal");
                const btnSave = document.getElementById("btnConfirmConvert");

                // 1. Ruta API
                if (btnSave) {
                    btnSave.dataset.route = `${apiUrl}/${id}/convert`;
                }

                // 2. Normalizar total
                const cleanTotal = total
                    ?.replace(/<[^>]*>/g, "")
                    .replace(/[^\d,.-]/g, "")
                    .replace(",", ".");

                const modalInstance =
                    bootstrap.Modal.getOrCreateInstance(modalElement);

                modalElement.addEventListener(
                    "shown.bs.modal",
                    () => {
                        setupConvertModal({
                            orderId: id,
                            total: cleanTotal,
                            customerType: customer_type,
                        });
                    },
                    { once: true }
                );

                modalInstance.show();
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

function setupConvertModal({ orderId, total, customerType }) {
    const displayId = document.getElementById("display_order_id");
    const inputAmount = document.getElementById("convert_amount_received");
    const selectPayment = document.getElementById("convert_payment_type");

    if (displayId) displayId.textContent = orderId;

    if (inputAmount) {
        inputAmount.value = total;
        inputAmount.readOnly = true;
    }

    if (!selectPayment || !selectPayment._choices) return;

    const isBranch = customerType?.includes("Branch");
    const paymentValue = isBranch ? "3" : "1";

    selectPayment._choices.removeActiveItems();
    selectPayment._choices.setChoiceByValue(paymentValue);

    selectPayment._choices.disable();
}

function resetConvertModal() {
    const selectPayment = document.getElementById("convert_payment_type");
    const inputAmount = document.getElementById("convert_amount_received");

    if (selectPayment && selectPayment._choices) {
        selectPayment._choices.enable();
        selectPayment.closest(".choices")?.classList.remove("is-disabled");
    }

    if (inputAmount) {
        inputAmount.value = "";
        inputAmount.removeAttribute("readonly");
    }
}

export function initOrderTable() {
    const modalElement = document.getElementById("convertOrderModal");

    if (modalElement && !modalElement.dataset.listenerAttached) {
        modalElement.addEventListener("hidden.bs.modal", () => {
            resetConvertModal();
        });

        modalElement.dataset.listenerAttached = "true";
    }

    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initOrderTable,
    config: TABLE_CONFIG,
};
