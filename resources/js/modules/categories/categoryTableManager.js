import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";
import { UIHelper } from "../../components/UIHelper";

// ─── Configuración de la tabla ───────────────────────────────────────────────

const TABLE_CONFIG = {
    tableId: "categories-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                window.location.href = `${baseUrl}/${row.dataset.id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;
                deleteItem(`${baseUrl}/${id}`, `la categoría "${name}"`);
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
    },
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

const getCsrfToken = () =>
    document.querySelector('meta[name="csrf-token"]').getAttribute("content");

const getBaseUrl = (element) => element.closest(".card")?.dataset.baseUrl;

const setRadioGroupDisabled = (name, disabled) =>
    document
        .querySelectorAll(`input[name="${name}"]`)
        .forEach((r) => (r.disabled = disabled));

// ─── Lógica de actualización de target ───────────────────────────────────────

async function updateCategoryTarget(radio, baseUrl) {
    const categoryId = radio.dataset.id;
    const newTarget = parseInt(radio.value);

    setRadioGroupDisabled(radio.name, true);

    try {
        const response = await fetch(`${baseUrl}/${categoryId}/update-target`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify({ _method: "PATCH", target: newTarget }),
        });

        const data = await response.json();

        if (!response.ok)
            throw new Error(data.message || "Error al actualizar");

        UIHelper.success(data.message || "Destino actualizado");
    } catch (error) {
        UIHelper.error(error.message);
        setTimeout(() => window.location.reload(), 1000);
    } finally {
        setRadioGroupDisabled(radio.name, false);
    }
}

// ─── Listener de cambios en radio buttons ────────────────────────────────────

function handleTableChange(tableElement) {
    tableElement.addEventListener("change", (e) => {
        const radio = e.target.closest(".btn-update-target");
        if (!radio) return;

        const baseUrl = getBaseUrl(tableElement);
        updateCategoryTarget(radio, baseUrl);
    });
}

// ─── Inicialización ──────────────────────────────────────────────────────────

export function initCategoryTable() {
    const table = TableManager.initTable(TABLE_CONFIG);
    const tableElement = document.getElementById(TABLE_CONFIG.tableId);

    if (tableElement) handleTableChange(tableElement);

    return table;
}

export default { init: initCategoryTable, config: TABLE_CONFIG };
