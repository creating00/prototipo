import { TableManager } from "../../components/TableManager";

document.addEventListener("DOMContentLoaded", () => {
    // Inicializa la tabla de items de la venta
    TableManager.initTable({
        tableId: "sale-items-table",
        rowActions: {},
        headerActions: {},
    });

    // Manejo de la impresión del ticket/comprobante
    const btnPrint = document.querySelector(".btn-print-sale");
    if (btnPrint) {
        btnPrint.addEventListener("click", () => {
            window.print();
        });
    }
});
