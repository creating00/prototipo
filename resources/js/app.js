// Imports de terceros
import "./bootstrap";
import Alpine from "alpinejs";
import "admin-lte/dist/js/adminlte.js";

// SweetAlert 2
import Swal from "sweetalert2";

// Componentes UI
import Choices from "choices.js";
import "choices.js/public/assets/styles/choices.min.css";
import { OverlayScrollbars } from "overlayscrollbars";
import DataTable from "datatables.net-bs5";
import "datatables.net-responsive-bs5";

import { ChoicesHelper } from "./helpers/choices-helper";
import { DynamicModalManager } from "./components/DynamicModalManager";

// Componentes personalizados
import { DataTableManager } from "./components/DataTableManager";
import { DataTableActionsManager } from "./components/DataTableActionsManager";
import { AppManager } from "./app-manager";
import { configureGlobals } from "./config/window";
import { TABLE_CONFIGS } from "./config/tables";

import ToastManager from "./components/ToastManager";

// Configuración global (ANTES del arranque de Alpine)
configureGlobals({
    Alpine,
    Choices,
    DataTable,
    DataTableManager,
    DataTableActionsManager,
    Swal,
    ChoicesHelper,
});

// Alpine: registrar listeners ANTES de iniciar Alpine
document.addEventListener("alpine:init", () => {
    // App Manager
    const appManager = new AppManager(OverlayScrollbars, Choices);
    appManager.init();

    // Componentes de UI
    new ToastManager();
    new DynamicModalManager();

    // Tablas
    Object.values(TABLE_CONFIGS).forEach(({ selector, options }) => {
        if (selector !== TABLE_CONFIGS.PRODUCT_MODAL.selector) {
            DataTableManager.initAll(selector, options);
        }
    });
});

Alpine.start();
