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
                const { id, observation } = row.dataset;
                console.log(row.dataset);
                deleteItem(
                    `${baseUrl}/${id}`,
                    `el gasto "${observation || id}"`,
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
            handler: (baseUrl, event) => {
                UIHelper.handleImport(
                    event.currentTarget,
                    "import-expenses-excel-input",
                    `${baseUrl}/import`,
                    "gastos",
                );
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
