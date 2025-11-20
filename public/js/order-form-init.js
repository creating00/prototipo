// order-form-init.js
document.addEventListener("DOMContentLoaded", function () {
    if (typeof OrderForm !== "undefined") {
        window.orderForm = new OrderForm();
        window.orderForm.init();

        if (window.currentOrderId) {
            window.orderForm.loadOrderData(window.currentOrderId);
        }
    }
});

// Hacer métodos disponibles globalmente
window.showNewClientForm = function () {
    if (window.orderForm) {
        window.orderForm.client.showNewClientForm();
    }
};

window.hideNewClientForm = function () {
    if (window.orderForm) {
        window.orderForm.client.hideNewClientForm();
    }
};

window.clearClientSelection = function () {
    if (window.orderForm) {
        window.orderForm.client.clearClientSelection();
    }
};

window.addProductToTable = function () {
    if (window.orderForm) {
        window.orderForm.products.addProductToTable();
    }
};
