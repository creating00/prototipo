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
            // Dentro de TABLE_CONFIG.rowActions.convert
            handler: (row) => {
                // Extraemos los nuevos datos del dataset de la fila
                const { id, totals_json, customer_name, customer_type } =
                    row.dataset;
                const totals = JSON.parse(totals_json || "{}");

                const modalElement =
                    document.getElementById("convertOrderModal");
                const btnSave = document.getElementById("btnConfirmConvert");

                if (btnSave) {
                    btnSave.dataset.route = `${apiUrl}/${id}/convert`;
                }

                const modalInstance =
                    bootstrap.Modal.getOrCreateInstance(modalElement);

                modalElement.addEventListener(
                    "shown.bs.modal",
                    () => {
                        setupConvertModal({
                            orderId: id,
                            totals: totals, // Pasamos el objeto de totales
                            customerName: customer_name,
                            customerType: customer_type,
                        });
                    },
                    { once: true },
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

function setupConvertModal({ orderId, totals, customerName, customerType }) {
    const rate = window.currentExchangeRate || 1000;

    // 1. Referencias UI
    const displayId = document.getElementById("display_order_id");
    const displayCustomer = document.getElementById("display_customer_name");
    const displayArs = document.getElementById("display_total_ars");
    const displayUsd = document.getElementById("display_total_usd");
    const pureArsLabel = document.getElementById("subtotal_ars_pure");
    const pureUsdLabel = document.getElementById("subtotal_usd_pure");

    const inputAmount = document.getElementById("convert_amount_received");
    const selectPayment = document.getElementById("convert_payment_type");
    const hiddenTotalArs = document.getElementById("total_amount");

    // Validación de elementos críticos
    if (!inputAmount || !selectPayment || !selectPayment._choices) return;

    // 2. Cálculos de Totales
    const arsPure = parseFloat(totals[1] || 0);
    const usdPure = parseFloat(totals[2] || 0);

    const totalConsolidadoArs = arsPure + usdPure * rate;
    const totalConsolidadoUsd = usdPure + arsPure / rate;

    // 3. Actualizar UI Visual (Textos)
    if (displayId) displayId.textContent = orderId;
    if (displayCustomer) displayCustomer.textContent = customerName;

    if (displayArs) {
        displayArs.textContent = totalConsolidadoArs.toLocaleString("es-AR", {
            minimumFractionDigits: 2,
        });
    }
    if (displayUsd) {
        displayUsd.textContent = totalConsolidadoUsd.toLocaleString("en-US", {
            minimumFractionDigits: 2,
        });
    }

    if (pureArsLabel) pureArsLabel.textContent = `$ ${arsPure.toFixed(2)}`;
    if (pureUsdLabel) pureUsdLabel.textContent = `U$D ${usdPure.toFixed(2)}`;

    // 4. Lógica de Negocio (Sucursales vs Clientes)
    const isBranch = customerType?.includes("Branch");
    const paymentValue = isBranch ? "3" : "1"; // 3: Transferencia (asumido), 1: Efectivo

    // Guardar total base para el backend
    if (hiddenTotalArs) hiddenTotalArs.value = totalConsolidadoArs.toFixed(2);

    // Configurar Monto Recibido
    inputAmount.value = totalConsolidadoArs.toFixed(2);
    inputAmount.readOnly = isBranch;

    // 5. Gestión de Choices.js (Método de Pago)
    selectPayment._choices.removeActiveItems();
    selectPayment._choices.setChoiceByValue(paymentValue);

    if (isBranch) {
        selectPayment._choices.disable();
        selectPayment.closest(".choices")?.classList.add("is-disabled");
    } else {
        selectPayment._choices.enable();
        selectPayment.closest(".choices")?.classList.remove("is-disabled");
    }

    // 6. Inicializar cálculos y disparar eventos
    bindConvertAmountListener(totalConsolidadoArs);
    inputAmount.dispatchEvent(new Event("input"));
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
        inputAmount.readOnly = false;
    }
}

function calculatePaymentStatus({ total, received }) {
    const t = parseFloat(total) || 0;
    const r = parseFloat(received) || 0;

    let change = 0;
    let remaining = 0;
    let status = "pending";

    if (r >= t) {
        change = r - t;
        remaining = 0;
        status = "paid";
    } else {
        change = 0;
        remaining = t - r;
        status = "partial";
    }

    return { change, remaining, status };
}

function bindConvertAmountListener(total) {
    const inputAmount = document.getElementById("convert_amount_received");
    const inputChange = document.getElementById("convert_change_returned");
    const inputRemaining = document.getElementById("convert_remaining_balance");
    const statusContainer = document.getElementById("convert_payment_status");

    if (!inputAmount) return;

    inputAmount.addEventListener("input", () => {
        const { change, remaining, status } = calculatePaymentStatus({
            total,
            received: inputAmount.value,
        });

        if (inputChange) inputChange.value = change.toFixed(2);
        if (inputRemaining) inputRemaining.value = remaining.toFixed(2);

        if (statusContainer) {
            let badgeClass = "bg-secondary";
            let label = "Esperando datos";

            if (status === "paid") {
                badgeClass = "bg-success";
                label = "Pagado";
            } else if (status === "partial") {
                badgeClass = "bg-warning";
                label = "Pago Parcial";
            }

            statusContainer.innerHTML = `<span class="badge ${badgeClass}">${label}</span>`;
        }
    });
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
