import { initSaleForm } from "./form";

document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("existing_order_items");

    initSaleForm({
        existingItems: input ? JSON.parse(input.value || "[]") : [],
    });
});
