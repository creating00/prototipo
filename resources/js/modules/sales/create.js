// resources/js/modules/sales/create.js
import { initSaleForm } from "./form";
import { initCartTransfer } from "./partials/cart-transfer";

document.addEventListener("DOMContentLoaded", () => {
    initSaleForm();
    initCartTransfer();
});
