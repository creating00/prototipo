// resources/js/modules/orders/partials/order-row.js
import { calculateSubtotal } from "./order-utils";

export function bindQuantityChange(row, updateCallbacks) {
    const qty = row.querySelector(".quantity");
    qty.addEventListener("input", () => {
        updateQuantity(row, parseInt(qty.value), updateCallbacks);
    });
}

export function updateQuantity(row, value, updateCallbacks) {
    row.querySelector(".quantity").value = value < 1 ? 1 : value;
    updateSubtotal(row);
    if (updateCallbacks?.updateTotal) updateCallbacks.updateTotal();
}

export function updateSubtotal(row) {
    const qty = row.querySelector(".quantity").value;
    const price = row.querySelector(".unit-price").value;
    row.querySelector(".subtotal").value = calculateSubtotal(qty, price);
}

export function addRow(table, html, updateCallbacks) {
    // Generar un índice único (timestamp + random para evitar colisiones)
    const uniqueIndex = Date.now() + Math.floor(Math.random() * 1000);

    // Reemplazar todas las ocurrencias de INDEX por el índice real
    const finalizedHtml = html.replace(/INDEX/g, uniqueIndex);

    // Insertar el HTML ya procesado
    table.insertAdjacentHTML("beforeend", finalizedHtml);

    const newRow = table.lastElementChild;
    bindQuantityChange(newRow, updateCallbacks);
    updateSubtotal(newRow);

    if (updateCallbacks?.updateTotal) updateCallbacks.updateTotal();

    return newRow;
}
