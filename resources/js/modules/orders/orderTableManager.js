import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";
import ConvertPaymentManager from "./partials/convertPaymentManager";
import CurrencyLoader from "@/modules/sales/services/currency-loader";

// 1. Extraemos la URL base del componente Blade (ej: /web/orders)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";
const apiUrl = tableContainer ? tableContainer.dataset.apiUrl : "/api/orders";
let paymentManager = null;

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
                // 1. Extraer exchangeRate del dataset
                const {
                    id,
                    totals_json,
                    customer_name,
                    customer_type,
                    exchange_rate,
                } = row.dataset;

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
                            totals: totals,
                            customerName: customer_name,
                            customerType: customer_type,
                            rowExchangeRate: exchange_rate, // Pasar el valor extraído
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

async function setupConvertModal({
    orderId,
    totals,
    customerName,
    customerType,
    rowExchangeRate,
}) {
    // 0. Identificar tipo
    const isBranch = customerType?.includes("Branch");
    const type = isBranch ? "compra" : "venta";

    // 1. Obtener Rate ANTES de los cálculos
    let rate;
    const parsedRowRate = parseFloat(rowExchangeRate);

    if (isBranch && !isNaN(parsedRowRate) && parsedRowRate > 0) {
        rate = parsedRowRate;
    } else {
        rate = (await CurrencyLoader.init(type)) || 1000;
    }

    const rateInput = document.getElementById("exchange_rate_blue");
    if (rateInput) rateInput.value = rate;

    // 2. Referencias UI
    const displayId = document.getElementById("display_order_id");
    const displayCustomer = document.getElementById("display_customer_name");
    const displayArs = document.getElementById("display_total_ars");
    const displayUsd = document.getElementById("display_total_usd");
    const pureArsLabel = document.getElementById("subtotal_ars_pure");
    const pureUsdLabel = document.getElementById("subtotal_usd_pure");
    const inputAmount = document.getElementById("convert_amount_received");
    const selectPayment = document.getElementById("convert_payment_type");
    const hiddenTotalArs = document.getElementById("total_amount");

    if (!inputAmount || !selectPayment || !selectPayment._choices) return;

    // 3. Cálculos base (Ahora con rate definido)
    const arsPure = parseFloat(totals[1] || 0);
    const usdPure = parseFloat(totals[2] || 0);

    const totalConsolidadoArs = arsPure + usdPure * rate;
    const totalConsolidadoUsd = usdPure + arsPure / rate;

    // 4. Actualizar UI
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

    // 5. Configurar Inputs y Choices
    const paymentValue = isBranch ? "3" : "1";
    if (hiddenTotalArs) hiddenTotalArs.value = totalConsolidadoArs.toFixed(2);

    inputAmount.value = totalConsolidadoArs.toFixed(2);
    inputAmount.readOnly = isBranch;

    selectPayment._choices.removeActiveItems();
    selectPayment._choices.setChoiceByValue(paymentValue);

    if (isBranch) {
        selectPayment._choices.disable();
        selectPayment.closest(".choices")?.classList.add("is-disabled");
    } else {
        selectPayment._choices.enable();
        selectPayment.closest(".choices")?.classList.remove("is-disabled");
    }

    // 6. Iniciar Manager con las dependencias necesarias
    paymentManager?.destroy?.();
    paymentManager = new ConvertPaymentManager({
        exchangeRate: rate,
        isBranch: isBranch, // Pasar esto para evitar el ReferenceError
    });
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

export function initOrderTable() {
    const modalElement = document.getElementById("convertOrderModal");

    if (modalElement && !modalElement.dataset.listenerAttached) {
        modalElement.addEventListener("hidden.bs.modal", () => {
            paymentManager?.destroy();
            paymentManager = null;
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
