import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";
import { UIHelper } from "../../components/UIHelper";

const TABLE_CONFIG = {
    tableId: "promotion-images-table",
    rowActions: {
        toggleStatus: {
            selector: ".btn-toggle-status",
            handler: async (row, baseUrl) => {
                const { id } = row.dataset;
                const isCurrentlyActive = row.dataset.is_active === "1";
                const actionText = isCurrentlyActive ? "Desactivar" : "Activar";

                const result = await Swal.fire({
                    title: `¿${actionText} banner?`,
                    text: isCurrentlyActive
                        ? "La imagen dejará de mostrarse."
                        : "La imagen volverá a mostrarse.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: `Sí, ${actionText.toLowerCase()}`,
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: isCurrentlyActive
                        ? "#e74c3c"
                        : "#28a745",
                    cancelButtonColor: "#6c757d",
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch(
                            `${baseUrl}/${id}/toggle-status`,
                            {
                                method: "POST",
                                headers: {
                                    "X-CSRF-TOKEN": document
                                        .querySelector(
                                            'meta[name="csrf-token"]',
                                        )
                                        .getAttribute("content"),
                                    Accept: "application/json",
                                },
                            },
                        );

                        if (response.ok) {
                            UIHelper.success("Estado actualizado.");
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    } catch (error) {
                        UIHelper.error("No se pudo actualizar.");
                    }
                }
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                deleteItem(`${baseUrl}/${id}`, `este banner`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: () => {
                const fileInput = document.getElementById(
                    "direct-upload-input",
                );
                const form = document.getElementById("direct-upload-form");
                fileInput.click();
                fileInput.onchange = () => {
                    if (fileInput.files.length > 0) {
                        Swal.fire({
                            title: "Procesando...",
                            text: "Subiendo imagen...",
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading(),
                        });
                        form.submit();
                    }
                };
            },
        },
    },
};

export function initPromotionTable() {
    const manager = TableManager.initTable(TABLE_CONFIG);

    const applyButtonVisibility = () => {
        document
            .querySelectorAll(`#${TABLE_CONFIG.tableId} tbody tr`)
            .forEach((tr) => {
                const isActive = tr.dataset.is_active === "1";
                tr.querySelector(".btn-deactivate")?.classList.toggle(
                    "d-none",
                    !isActive,
                );
                tr.querySelector(".btn-activate")?.classList.toggle(
                    "d-none",
                    isActive,
                );
            });
    };

    // Ejecución inicial
    applyButtonVisibility();

    // Re-ejecución en cada redibujado de DataTables (paginación, búsqueda, etc.)
    $(`#${TABLE_CONFIG.tableId}`).on("draw.dt", applyButtonVisibility);

    return manager;
}

export default {
    init: initPromotionTable,
    config: TABLE_CONFIG,
};
