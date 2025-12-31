// resources/js/modules/sales/create.js
import salesItems from "./partials/sales-items";
import orderModal from "../../modules/orders/partials/order-modal";
import orderSearch from "../../modules/orders/partials/order-search";
import customerType from "../../modules/orders/partials/customer-type";
import branchFilter from "../../modules/orders/partials/branch-filter";
import ShortcutManager from "../../helpers/ShortcutManager.js";
import ViewManager from "../../helpers/ViewManager.js";
import salePayment from "./partials/sale-payment";
import saleDiscount from "./partials/sale-discount";
import saleSummary from "./partials/sale-summary";

import AccordionAutoScroll from "../../helpers/AccordionAutoScroll.js";

document.addEventListener("DOMContentLoaded", () => {
    // 1. Inicialización de componentes
    customerType.init();
    saleDiscount.init();
    salePayment.init();
    saleSummary.init();
    salesItems.init();
    orderSearch.init();
    orderModal.init();
    branchFilter.init();

    // 2. Configuración de Atajos
    const shortcuts = [
        {
            key: "F1",
            allowInInputs: true,
            action: () => {
                const searchInput = document.getElementById(
                    "product_search_code"
                );
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select(); // Selecciona el texto para sobreescribir rápido
                }
            },
        },
        {
            key: "F2",
            allowInInputs: true,
            action: () => ShortcutManager.openModal("modalClient"),
        },
        {
            key: "F4",
            allowInInputs: true, // Permitimos abrir la lista incluso si está en un input
            action: () => {
                ShortcutManager.openModal(
                    "modal-product-search",
                    'input[type="search"]'
                );
            },
        },
        {
            key: "b",
            ctrl: true,
            allowInInputs: false,
            action: () =>
                ShortcutManager.openModal(
                    "modal-product-search",
                    'input[type="search"]'
                ),
        },
        {
            key: "F10",
            allowInInputs: true,
            action: () =>
                ShortcutManager.openModal(
                    "modalSalePayment",
                    "#amount_received"
                ),
        },
        {
            key: "F12",
            allowInInputs: true, // Importante: que pueda guardar incluso si está escribiendo el monto
            action: () => {
                const form = document.getElementById("saleForm");
                if (form) {
                    // Disparamos el evento submit de forma que AlpineJS capture el 'submitting = true'
                    form.requestSubmit();
                }
            },
        },
    ];
    new ShortcutManager(shortcuts);

    // 3. Gestión de Vista (Scroll y Foco)
    ViewManager.initSmartScroll(
        ".card-primary.card-outline",
        "#product_search_code"
    );
    //new AccordionAutoScroll("saleFormAccordion", 100); (comentado por ahora)
});
