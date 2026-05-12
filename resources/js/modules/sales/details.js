import { TableManager } from "../../components/TableManager";

document.addEventListener("DOMContentLoaded", () => {
    // Inicializa la tabla
    TableManager.initTable({
        tableId: "sale-items-table",
        rowActions: {},
        headerActions: {},
    });

    const btnPrint = document.querySelector(".btn-print-sale");
    const modalEl = document.getElementById("modalPrintSale");

    if (btnPrint && modalEl) {
        btnPrint.addEventListener("click", () => {
            // Extraemos los datos del dataset del botón
            const { id, baseUrl } = btnPrint.dataset;

            const ticketLink = modalEl.querySelector("#linkPrintTicket");
            const a4Link = modalEl.querySelector("#linkPrintA4");
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            // Inyectamos las rutas
            ticketLink.href = `${baseUrl}/${id}/ticket`;
            a4Link.href = `${baseUrl}/${id}/a4`;

            // Configuramos el cierre automático
            const closeLabels = () => setTimeout(() => modal.hide(), 100);
            ticketLink.onclick = closeLabels;
            a4Link.onclick = closeLabels;

            modal.show();
        });
    }
});
