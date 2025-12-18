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
    table.insertAdjacentHTML("beforeend", html);
    const newRow = table.lastElementChild;
    bindQuantityChange(newRow, updateCallbacks);
    updateSubtotal(newRow);
    if (updateCallbacks?.updateTotal) updateCallbacks.updateTotal();
    return newRow;
}
