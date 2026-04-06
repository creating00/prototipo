import { TableManager } from "../../components/TableManager";

const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const TABLE_CONFIG = {
    tableId: "notifications-table",

    rowActions: {
        view: {
            selector: ".btn-view",
            handler: (row) => {
                const { order_id } = row.dataset;

                if (order_id) {
                    const ordersUrl = baseUrl.replace(
                        "/notifications",
                        "/orders",
                    );
                    window.location.href = `${ordersUrl}/${order_id}`;
                }
            },
        },

        markRead: {
            selector: ".btn-mark-read",
            handler: async (row, baseUrl) => {
                const { id, is_read } = row.dataset;

                if (is_read === "1") return;

                try {
                    const response = await fetch(`${baseUrl}/${id}/mark-read`, {
                        method: "PATCH",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                    });

                    if (response.ok) {
                        window.location.reload();
                    }
                } catch (error) {
                    console.error(
                        "Error al actualizar la notificación:",
                        error,
                    );
                }
            },
        },
    },

    headerActions: {
        markAll: {
            selector: ".btn-header-mark-all",
            handler: async (baseUrl) => {
                try {
                    const response = await fetch(`${baseUrl}/mark-all-read`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                    });

                    if (response.ok) {
                        window.location.reload();
                    }
                } catch (error) {
                    console.error("Error al marcar notificaciones:", error);
                }
            },
        },
    },
};

export function initNotificationTable() {
    const tableInstance = TableManager.initTable(TABLE_CONFIG);

    const tbody = document.querySelector(`#${TABLE_CONFIG.tableId} tbody`);

    if (tbody) {
        // Funcion para alternar botones iterando filas
        const hideReadButtons = () => {
            tbody.querySelectorAll("tr").forEach((row) => {
                if (row.dataset.is_read === "1") {
                    const markReadBtn = row.querySelector(".btn-mark-read");
                    const readIndicator = row.querySelector(".read-indicator");

                    // Ocultar botón activo
                    if (markReadBtn) {
                        markReadBtn.classList.add("d-none");
                    }

                    // Mostrar botón deshabilitado
                    if (readIndicator) {
                        readIndicator.classList.remove("d-none");
                    }
                }
            });
        };

        // Ejecucion inicial
        hideReadButtons();

        // Observador para cambios en la tabla (paginacion, busqueda)
        const observer = new MutationObserver(hideReadButtons);
        observer.observe(tbody, { childList: true });
    }

    return tableInstance;
}

export default {
    init: initNotificationTable,
    config: TABLE_CONFIG,
};
