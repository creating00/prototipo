import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";

const TABLE_CONFIG = {
    tableId: "sales-table",
    rowActions: {
        print: {
            selector: ".btn-print",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                const modalEl = document.getElementById("modalPrintSale");

                if (!modalEl) return;

                const ticketLink = modalEl.querySelector("#linkPrintTicket");
                const a4Link = modalEl.querySelector("#linkPrintA4");
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

                // Actualizar rutas
                ticketLink.href = `${baseUrl}/${id}/ticket`;
                a4Link.href = `${baseUrl}/${id}/a4`;

                // Función para cerrar modal tras click
                const closeLabels = () => {
                    // Un pequeño delay permite que el navegador procese la apertura de la nueva pestaña
                    setTimeout(() => modal.hide(), 100);
                };

                // Asignar evento de cierre (limpiando previos para evitar duplicados)
                ticketLink.onclick = closeLabels;
                a4Link.onclick = closeLabels;

                modal.show();
            },
        },
        detail: {
            selector: ".btn-view",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                window.location.href = `${baseUrl}/${id}/details`;
            },
        },
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset; // name o el campo que uses para mostrar
                deleteItem(`${baseUrl}/${id}`, `la venta de "${name || id}"`);
            },
        },
    },
    headerActions: {
        newClient: {
            selector: ".btn-header-new-client",
            handler: (baseUrl) => {
                window.location.href = `${baseUrl}/create-client`;
            },
        },
        newBranch: {
            selector: ".btn-header-new-branch",
            handler: (baseUrl) => {
                window.location.href = `${baseUrl}/create-branch`;
            },
        },
    },
};

export function initSalesTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initSalesTable,
    config: TABLE_CONFIG,
};
