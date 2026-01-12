// resources/js/modules/sales/form.js
import salesItems from "./partials/sales-items";
// import orderModal from "../../modules/orders/partials/order-modal";
// import orderSearch from "../../modules/orders/partials/order-search";
import customerType from "../../modules/orders/partials/customer-type";
import branchFilter from "../../modules/orders/partials/branch-filter";
import ShortcutManager from "../../helpers/ShortcutManager.js";
import ViewManager from "../../helpers/ViewManager.js";
import salePayment from "./partials/sale-payment";
import saleDiscount from "./partials/sale-discount";
import saleSummary from "./partials/sale-summary";
import productAutocomplete from "./partials/product-autocomplete";

export function initSaleForm({ existingItems = [] } = {}) {
    const customerTypeInput = document.querySelector(
        'input[name="customer_type"]'
    );
    const isBranch = customerTypeInput?.value === "App\\Models\\Branch";

    if (isBranch) {
        // Buscamos el select de descuento o su contenedor
        const discountSelect = document.getElementById("discount_id");
        if (discountSelect) {
            // Ocultamos el col-6 que contiene el label y el select
            const wrapper = discountSelect.closest(".col-6");
            if (wrapper) wrapper.style.display = "none";

            // También ocultamos el div de "Descuento aplicado" (el siguiente col-6)
            const appliedDiscountWrapper = wrapper.nextElementSibling;
            if (appliedDiscountWrapper)
                appliedDiscountWrapper.style.display = "none";
        }
    }

    // 1. Inicialización de componentes
    productAutocomplete.init();
    customerType.init();
    saleDiscount.init();
    salePayment.init();
    saleSummary.init();
    salesItems.init();
    //orderSearch.init();
    //orderModal.init();
    branchFilter.init();

    if (existingItems.length) {
        salesItems.preload(existingItems);
    }

    // 2. Atajos
    const shortcuts = [
        {
            key: "F1",
            allowInInputs: true,
            action: () => {
                const searchInput = document.getElementById(
                    "product_search_input"
                );
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            },
        },
        {
            key: "F2",
            allowInInputs: true,
            action: () => ShortcutManager.openModal("modalClient"),
        },
        // {
        //     key: "F4",
        //     allowInInputs: true,
        //     action: () =>
        //         ShortcutManager.openModal(
        //             "modal-product-search",
        //             'input[type="search"]'
        //         ),
        // },
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
            allowInInputs: true,
            action: () => {
                const form = document.getElementById("saleForm");
                if (form) {
                    form.requestSubmit();
                }
            },
        },
    ];

    new ShortcutManager(shortcuts);

    // 3. Vista
    ViewManager.initSmartScroll(
        ".card-primary.card-outline",
        "#product_search_input"
    );
}
