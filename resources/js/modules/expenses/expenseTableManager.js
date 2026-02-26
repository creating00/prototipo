import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";
import { UIHelper } from "../../components/UIHelper";

// 1. Extraemos la URL base del componente Blade (ej: /web/expenses)
const tableContainer = document.querySelector("[data-base-url]");
const baseUrl = tableContainer ? tableContainer.dataset.baseUrl : "";

const TABLE_CONFIG = {
    tableId: "expenses-table",

    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Redirección dinámica
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },

        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, description } = row.dataset;
                // Usamos description o el campo que tengas mapeado en el Service
                deleteItem(
                    `${baseUrl}/${id}`,
                    `el gasto "${description || id}"`,
                );
            },
        },
    },

    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: (baseUrl) => {
                window.location.href = `${baseUrl}/create`;
            },
        },

        importExpenses: {
            selector: ".btn-header-import-expenses",
            handler: (baseUrl) => {
                const fileInput = document.getElementById(
                    "import-expenses-excel-input",
                );
                const btn = document.querySelector(
                    ".btn-header-import-expenses",
                );

                if (!fileInput) return;

                fileInput.value = "";
                fileInput.onchange = async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;

                    const allowed = ["xlsx", "xls", "csv"];
                    const ext = file.name.split(".").pop().toLowerCase();

                    if (!allowed.includes(ext)) {
                        UIHelper.error(
                            `Formato no válido. Use: ${allowed.join(", ")}`,
                        );
                        fileInput.value = "";
                        return;
                    }

                    UIHelper.disableButton(btn, "Subiendo...");

                    Swal.fire({
                        title: "Importando gastos...",
                        text: "Analizando archivo y procesando registros",
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });

                    const formData = new FormData();
                    formData.append("file", file);

                    try {
                        // Usamos la ruta del controlador que creamos anteriormente
                        const { data } = await axios.post(
                            `${baseUrl}/import`,
                            formData,
                            {
                                headers: {
                                    "Content-Type": "multipart/form-data",
                                },
                            },
                        );

                        UIHelper.success(
                            data.message || "Gastos importados con éxito",
                        );
                        setTimeout(() => window.location.reload(), 1500);
                    } catch (error) {
                        console.error(error);
                        const msg =
                            error.response?.data?.error ||
                            "Error al importar gastos";
                        Swal.fire("Error", msg, "error");
                    } finally {
                        UIHelper.enableButton(btn);
                    }
                };

                fileInput.click();
            },
        },

        // Acción para descargar la plantilla
        downloadTemplate: {
            selector: ".btn-download-template",
            handler: (baseUrl, event) => {
                UIHelper.handleDownload(event.currentTarget, event);
            },
        },
    },
};

function getLockedTemplate() {
    const tpl = document.getElementById("expense-actions-locked-template");
    return tpl ? tpl.content.cloneNode(true) : null;
}

function applyBranchActionRestrictions() {
    const wrapper = document.getElementById("expenses-table-wrapper");
    if (!wrapper) return;

    const currentBranchId = parseInt(wrapper.dataset.currentBranchId);
    const lockedTemplate = getLockedTemplate();

    document.querySelectorAll("#expenses-table tbody tr").forEach((row) => {
        const branchId = parseInt(row.dataset.branchId);

        if (branchId !== currentBranchId) {
            const actionsCell = row.querySelector("td.text-center");
            if (!actionsCell || actionsCell.dataset.locked === "true") return;

            actionsCell.innerHTML = "";
            if (lockedTemplate) {
                actionsCell.appendChild(lockedTemplate.cloneNode(true));
            }

            actionsCell.dataset.locked = "true";
        }
    });
}

export function initExpenseTable() {
    TableManager.initTable(TABLE_CONFIG);
    applyBranchActionRestrictions();
}

export default {
    init: initExpenseTable,
    config: TABLE_CONFIG,
};
