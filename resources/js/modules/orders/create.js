import orderItems from "./partials/order-items";
import orderModal from "./partials/order-modal";
import orderSearch from "./partials/order-search";
import customerType from "./partials/customer-type";
import branchFilter from "./partials/branch-filter";

document.addEventListener("DOMContentLoaded", () => {
    // Inicializar en un orden lógico:
    // 1. Elementos básicos del formulario
    customerType.init();

    // 2. Elementos de productos (dependen del formulario ya cargado)
    orderItems.init();
    orderSearch.init();

    // 3. Modal y filtros (dependen de que los elementos anteriores estén listos)
    orderModal.init();

    // 4. Filtros de sucursal (dependen del modal)
    branchFilter.init();
});
