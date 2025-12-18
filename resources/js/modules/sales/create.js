// resources/js/modules/sales/create.js
import orderItems from "../../modules/orders/partials/order-items.js";
import orderModal from "../../modules/orders/partials/order-modal";
import orderSearch from "../../modules/orders/partials/order-search";
import customerType from "../../modules/orders/partials/customer-type";
import branchFilter from "../../modules/orders/partials/branch-filter";
import salePayment from "./partials/sale-payment";

document.addEventListener("DOMContentLoaded", () => {
    // Inicializar en un orden lógico:
    // 1. Elementos básicos del formulario
    customerType.init();

    // 2. Sistema de pagos (depende del tipo de cliente)
    salePayment.init();

    // 3. Elementos de productos (dependen del formulario ya cargado)
    orderItems.init();
    orderSearch.init();

    // 4. Modal y filtros (dependen de que los elementos anteriores estén listos)
    orderModal.init();

    // 5. Filtros de sucursal (dependen del modal)
    branchFilter.init();
});
