// resources/js/modules/orders/partials/order-row.js
import { calculateSubtotal } from "./order-utils";

export function bindQuantityChange(row, updateCallbacks) {
    const qty = row.querySelector(".quantity");
    if (qty) {
        qty.addEventListener("input", () => {
            updateQuantity(row, parseFloat(qty.value) || 0, updateCallbacks);
        });
    }
}

export function bindPriceChange(row, updateCallbacks) {
    const priceInput = row.querySelector(".unit-price");
    if (priceInput) {
        priceInput.addEventListener("input", () => {
            updateSubtotal(row);
            if (updateCallbacks?.updateTotal) updateCallbacks.updateTotal();
        });
        priceInput.addEventListener("change", () => {
            updateSubtotal(row);
            if (updateCallbacks?.updateTotal) updateCallbacks.updateTotal();
        });
    }
}

export function updateQuantity(row, value, updateCallbacks) {
    const qtyInput = row.querySelector(".quantity");
    if (qtyInput) {
        qtyInput.value = value < 1 ? 1 : value;
    }
    updateSubtotal(row);
    if (updateCallbacks?.updateTotal) updateCallbacks.updateTotal();
}

export function updateSubtotal(row) {
    const qty = parseFloat(row.querySelector(".quantity")?.value) || 0;
    const price = parseFloat(row.querySelector(".unit-price")?.value) || 0;
    const subtotalInput = row.querySelector(".subtotal");
    if (subtotalInput) {
        subtotalInput.value = calculateSubtotal(qty, price);
    }
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
    bindPriceChange(newRow, updateCallbacks);
    updateSubtotal(newRow);

    if (updateCallbacks?.updateTotal) updateCallbacks.updateTotal();

    return newRow;
}

