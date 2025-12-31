import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { glob } from "glob";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/login.css",
                "resources/css/modules/sales/sales-styles.css",
                "resources/css/modules/branches/branches-styles.css",
                "resources/js/app.js",
                "resources/js/adminlte-components.js",
                "resources/js/pages/analytics-dashboard.js",
                ...glob.sync("resources/js/modules/**/*.js"), // Esto busca todo en módulos
            ],
            refresh: true,
        }),
    ],
});
