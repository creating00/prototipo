import { TableManager } from "../../components/TableManager";
import {
    handleOrderConversion,
    handleOrderPrint,
    resetOrderConvertModal,
} from "./orderActions";

document.addEventListener("DOMContentLoaded", () => {
    // 1. Inicializar tabla de ítems (solo visual)
    TableManager.initTable({
        tableId: "order-items-table",
        rowActions: {},
        headerActions: {},
    });

    // 2. Configurar el botón de Conversión
    const btnConvert = document.querySelector(".btn-convert");
    if (btnConvert) {
        btnConvert.addEventListener("click", () => {
            // El primer parámetro es el elemento que tiene los data-attributes
            // El segundo es la URL base de la API (si no viene en el dataset)
            const apiUrl = btnConvert.dataset.apiUrl || "/api/orders";
            handleOrderConversion(btnConvert, apiUrl);
        });
    }

    // 3. Manejar el cierre del modal (Reset)
    const modalElement = document.getElementById("convertOrderModal");
    if (modalElement) {
        modalElement.addEventListener("hidden.bs.modal", () => {
            resetOrderConvertModal();
        });
    }

    // Botón de impresión simple
    const btnPrint = document.querySelector(".btn-print");
    if (btnPrint) {
        btnPrint.addEventListener("click", () => {
            const saleUrl = "/web/sales";
            handleOrderPrint(btnPrint, saleUrl);
        });
    }
});
