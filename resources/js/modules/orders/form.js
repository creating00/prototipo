import orderItems from "./partials/order-items";
import orderModal from "./partials/order-modal";
// import orderSearch from "./partials/order-search";
import customerType from "./partials/customer-type";
import branchFilter from "./partials/branch-filter";
import productAutocomplete from "../sales/partials/product-autocomplete";
import ShortcutManager from "../../helpers/ShortcutManager.js";
import ViewManager from "../../helpers/ViewManager.js";

import orderCurrency from "./partials/order-currency";

const OrderForm = {
    init() {
        document.addEventListener("DOMContentLoaded", () => {
            this.initModules();
            this.initShortcuts();
            this.initView();
            this.loadExistingItems();
        });
    },

    initModules() {
        if (customerType) customerType.init();
        orderItems.init();
        // orderSearch.init();
        orderCurrency.init();
        orderModal.init();
        if (branchFilter) branchFilter.init();
        if (productAutocomplete) productAutocomplete.init({ context: "order" });
    },

    initShortcuts() {
        const shortcuts = [
            {
                key: "F1",
                allowInInputs: true,
                action: () => {
                    const searchInput = document.getElementById(
                        "product_search_input",
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
                action: () => {
                    // Solo intenta abrir el modal si el botón existe (caso cliente)
                    if (document.getElementById("btn-new-client")) {
                        ShortcutManager.openModal("modalClient");
                    }
                },
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
                key: "F12",
                allowInInputs: true,
                action: () => {
                    // Busca el formulario principal de órdenes (asegúrate de que este ID exista en tu Blade)
                    const form = document.querySelector(
                        'form[action*="orders"]',
                    );
                    if (form) form.requestSubmit();
                },
            },
        ];

        new ShortcutManager(shortcuts);
    },

    initView() {
        // Mantiene la tarjeta de origen visible mientras haces scroll en la lista de productos
        ViewManager.initSmartScroll(
            ".card-primary.card-outline",
            "#product_search_input",
        );
    },

    loadExistingItems() {
        const existingItemsInput = document.querySelector(
            "#existing_order_items",
        );
        if (!existingItemsInput) return;

        try {
            const existingItems = JSON.parse(existingItemsInput.value || "[]");
            if (existingItems.length > 0) {
                console.log(`Cargando ${existingItems.length} productos...`);
                existingItems.forEach((item) => {
                    orderItems.addRow(item.html || item);
                });

                orderItems.refreshTableState();
            }
        } catch (error) {
            console.error("Error al parsear productos existentes:", error);
        }
    },
};

OrderForm.init();

export default OrderForm;
