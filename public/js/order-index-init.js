// order-index-init.js
document.addEventListener("DOMContentLoaded", function () {
    if (typeof OrderIndexHandler !== "undefined") {
        window.orderIndexHandler = new OrderIndexHandler();

        window.orderIndexHandler.loadOrders().then(() => {
            if (typeof DataTableInitializer !== "undefined") {
                const orderTable = new DataTableInitializer("#tableOrders");
                orderTable.initialize();
            } else {
                console.warn("La clase DataTableInitializer no está definida.");
            }
        });
    }
});
