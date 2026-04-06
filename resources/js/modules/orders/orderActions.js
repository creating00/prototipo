import ConvertPaymentManager from "./partials/convertPaymentManager";
import CurrencyLoader from "@/modules/sales/services/currency-loader";

// Variable privada del módulo para controlar la instancia de pagos
let currentPaymentManager = null;

export const handleOrderConversion = (row, apiUrl) => {
    const { id, totals_json, customer_name, customer_type, exchange_rate } =
        row.dataset;
    const totals = JSON.parse(totals_json || "{}");
    const modalElement = document.getElementById("convertOrderModal");
    const btnSave = document.getElementById("btnConfirmConvert");

    if (!modalElement) return;

    if (btnSave) {
        btnSave.dataset.route = `${apiUrl}/${id}/convert`;
    }

    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);

    // IMPORTANTE: Llamamos directamente a la función que está abajo
    const onModalShown = () => {
        setupConvertModal({
            orderId: id,
            totals: totals,
            customerName: customer_name,
            customerType: customer_type,
            rowExchangeRate: exchange_rate,
        });
    };

    modalElement.addEventListener("shown.bs.modal", onModalShown, {
        once: true,
    });
    modalInstance.show();
};

export async function setupConvertModal({
    orderId,
    totals,
    customerName,
    customerType,
    rowExchangeRate,
}) {
    const isBranch = customerType?.includes("Branch");
    const type = isBranch ? "compra" : "venta";

    let rate;
    const parsedRowRate = parseFloat(rowExchangeRate);
    if (isBranch && !isNaN(parsedRowRate) && parsedRowRate > 0) {
        rate = parsedRowRate;
    } else {
        rate = (await CurrencyLoader.init(type)) || 1000;
    }

    const rateInput = document.getElementById("exchange_rate_blue");
    if (rateInput) rateInput.value = rate;

    // Cálculos
    const arsPure = parseFloat(totals[1] || 0);
    const usdPure = parseFloat(totals[2] || 0);
    const totalConsolidadoArs = arsPure + usdPure * rate;
    const totalConsolidadoUsd = usdPure + arsPure / rate;

    // Actualizar UI usando la función helper setText
    setText("display_order_id", orderId);
    setText("display_customer_name", customerName);
    setText(
        "display_total_ars",
        totalConsolidadoArs.toLocaleString("es-AR", {
            minimumFractionDigits: 2,
        }),
    );
    setText(
        "display_total_usd",
        totalConsolidadoUsd.toLocaleString("en-US", {
            minimumFractionDigits: 2,
        }),
    );
    setText("subtotal_ars_pure", `$ ${arsPure.toFixed(2)}`);
    setText("subtotal_usd_pure", `U$D ${usdPure.toFixed(2)}`);

    const inputAmount = document.getElementById("convert_amount_received");
    const selectPayment = document.getElementById("convert_payment_type");
    const hiddenTotalArs = document.getElementById("total_amount");

    if (!inputAmount || !selectPayment?._choices) return;

    if (hiddenTotalArs) hiddenTotalArs.value = totalConsolidadoArs.toFixed(2);
    inputAmount.value = totalConsolidadoArs.toFixed(2);
    inputAmount.readOnly = isBranch;

    const choices = selectPayment._choices;
    choices.removeActiveItems();
    choices.setChoiceByValue(isBranch ? "3" : "1");

    if (isBranch) {
        choices.disable();
        selectPayment.closest(".choices")?.classList.add("is-disabled");
    } else {
        choices.enable();
        selectPayment.closest(".choices")?.classList.remove("is-disabled");
    }

    // Gestionar el manager de pagos
    if (currentPaymentManager) {
        currentPaymentManager.destroy?.();
    }

    currentPaymentManager = new ConvertPaymentManager({
        exchangeRate: rate,
        isBranch: isBranch,
    });
}

export function resetOrderConvertModal() {
    // Destruir manager al cerrar
    if (currentPaymentManager) {
        currentPaymentManager.destroy?.();
        currentPaymentManager = null;
    }

    const selectPayment = document.getElementById("convert_payment_type");
    const inputAmount = document.getElementById("convert_amount_received");

    if (selectPayment?._choices) {
        selectPayment._choices.enable();
        selectPayment.closest(".choices")?.classList.remove("is-disabled");
    }

    if (inputAmount) {
        inputAmount.value = "";
        inputAmount.readOnly = false;
    }
}

/**
 * Abre el modal de impresión para una orden ya convertida a venta.
 * @param {HTMLElement} element - El elemento que contiene los data-attributes (fila o botón)
 * @param {string} saleUrl - La URL base para las rutas de venta (ej: /web/sales)
 */
export const handleOrderPrint = (element, saleUrl) => {
    const { sale_id } = element.dataset;
    const modalEl = document.getElementById("modalPrintSale");

    if (!modalEl) return;

    // Validación de seguridad
    if (!sale_id || sale_id === "null" || sale_id === "") {
        Swal.fire({
            icon: "warning",
            title: "No disponible",
            text: "Esta orden no tiene una venta asociada para imprimir.",
        });
        return;
    }

    const ticketLink = modalEl.querySelector("#linkPrintTicket");
    const a4Link = modalEl.querySelector("#linkPrintA4");
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    // Actualizar hrefs de los enlaces del modal
    if (ticketLink) ticketLink.href = `${saleUrl}/${sale_id}/ticket`;
    if (a4Link) a4Link.href = `${saleUrl}/${sale_id}/a4`;

    const closeLabels = () => {
        setTimeout(() => modal.hide(), 500);
    };

    if (ticketLink) ticketLink.onclick = closeLabels;
    if (a4Link) a4Link.onclick = closeLabels;

    modal.show();
};

function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}
