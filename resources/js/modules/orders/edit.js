import orderItems from "./partials/order-items";
import orderModal from "./partials/order-modal";
import orderSearch from "./partials/order-search";
import { addRow } from "./partials/order-row";

document.addEventListener("DOMContentLoaded", () => {
    orderItems.init();
    orderModal.init();
    orderSearch.init();

    // Cargar productos existentes del pedido
    const existingItems = JSON.parse(
        document.querySelector("#existing_order_items").value || "[]"
    );
    existingItems.forEach((item) => {
        orderItems.addRow(item.html);
    });
});
