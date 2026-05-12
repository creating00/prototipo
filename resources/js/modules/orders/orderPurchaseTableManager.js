import { TableManager } from "../../components/TableManager";
import BootstrapSwal from "../../config/sweetalert";
import { Toast } from "@/config/notifications";

// 1. Extraemos la URL base del componente Blade (ej: /web/orders)
const divContainer = document.querySelector("[data-base-url-origin]");
const originUrl = divContainer ? divContainer.dataset.baseUrlOrigin : "";

/**
 * Helper para peticiones POST rápidas
 */
const postAction = async (url, body = {}) => {
    const response = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
        body: JSON.stringify(body),
    });
    return await response.json();
};

const TABLE_CONFIG = {
    tableId: "purchases-table",
    rowActions: {
        view: {
            selector: ".btn-view",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                window.location.href = `${baseUrl}/${id}/details`;
            },
        },
        print: {
            selector: ".btn-print",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Abrir en pestaña nueva para imprimir
                window.open(`${baseUrl}/${id}/print`, "_blank");
            },
        },
        receive: {
            selector: ".btn-receive",
            handler: async (row) => {
                const { id } = row.dataset;
                const orderNumber =
                    row.querySelector("td:nth-child(2)")?.textContent.trim() ||
                    id;

                // 1. Confirmación usando el helper existente para el diálogo
                const { value: observation, isConfirmed } =
                    await BootstrapSwal.confirmReceiveWithObservation(
                        orderNumber
                    );

                if (isConfirmed) {
                    try {
                        const response = await fetch(
                            `${originUrl}/${id}/receive`,
                            {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": document.querySelector(
                                        'meta[name="csrf-token"]'
                                    ).content,
                                    Accept: "application/json",
                                },
                                body: JSON.stringify({ observation }),
                            }
                        );

                        const result = await response.json();

                        if (result.success) {
                            // Uso directo de Toast para éxito
                            await Toast.fire({
                                icon: "success",
                                title: "¡Recibido!",
                                text: result.message,
                                timer: 1500,
                            });
                            window.location.reload();
                        } else {
                            // Uso directo de Toast para error de lógica
                            Toast.fire({
                                icon: "error",
                                title: "Error",
                                text:
                                    result.message ||
                                    "No se pudo procesar la recepción.",
                            });
                        }
                    } catch (error) {
                        // Uso directo de Toast para error de red/sistema
                        Toast.fire({
                            icon: "error",
                            title: "Error de sistema",
                            text: "No se pudo conectar con el servidor.",
                        });
                    }
                }
            },
        },
    },
};

export function initOrderPurchaseTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initOrderPurchaseTable,
    config: TABLE_CONFIG,
};
