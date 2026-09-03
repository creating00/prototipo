import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

const inputs = [
    "resources/css/app.css",
    "resources/css/login.css",
    "resources/css/modules/sales/sales-styles.css",
    "resources/css/modules/branches/branches-styles.css",
    "resources/css/modules/products/products-styles.css",
    "resources/js/app.js",
    "resources/js/adminlte-components.js",
    "resources/js/pages/analytics-dashboard.js",
    "resources/js/modules/bank-accounts/index.js",
    "resources/js/modules/banks/index.js",
    "resources/js/modules/branches/index.js",
    "resources/js/modules/categories/index.js",
    "resources/js/modules/clients/create.js",
    "resources/js/modules/clients/index.js",
    "resources/js/modules/discounts/form.js",
    "resources/js/modules/discounts/index.js",
    "resources/js/modules/expenses/create.js",
    "resources/js/modules/expenses/index.js",
    "resources/js/modules/notifications/index.js",
    "resources/js/modules/orders/details.js",
    "resources/js/modules/orders/form.js",
    "resources/js/modules/orders/index.js",
    "resources/js/modules/orders/purchases.js",
    "resources/js/modules/products/index.js",
    "resources/js/modules/products/product-form.js",
    "resources/js/modules/promotions/index.js",
    "resources/js/modules/promotion_images/index.js",
    "resources/js/modules/provider-order/form.js",
    "resources/js/modules/provider-order/index.js",
    "resources/js/modules/providers/index.js",
    "resources/js/modules/providers/show.js",
    "resources/js/modules/repair-amounts/index.js",
    "resources/js/modules/sales/create.js",
    "resources/js/modules/sales/details.js",
    "resources/js/modules/sales/edit.js",
    "resources/js/modules/sales/index.js",
    "resources/js/modules/users/index.js",
];

export default defineConfig({
    plugins: [
        laravel({
            input: inputs,
            refresh: true,
        }),
    ],
});
