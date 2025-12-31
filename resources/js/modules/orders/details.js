import { TableManager } from "../../components/TableManager";

document.addEventListener("DOMContentLoaded", () => {
    // Inicializamos la tabla de items (productos del pedido)
    // Pasamos una configuración mínima porque withActions es "false"
    TableManager.initTable({
        tableId: "order-items-table",
        rowActions: {}, // Sin acciones de fila para los productos
        headerActions: {},
    });

    // Aquí puedes agregar lógica para botones que NO están en la tabla
    // Por ejemplo, el botón de imprimir que está en la cabecera
    const btnPrint = document.querySelector(".btn-print-order");
    if (btnPrint) {
        btnPrint.addEventListener("click", () => {
            window.print(); // O tu lógica de generación de PDF
        });
    }
});
