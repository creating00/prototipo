document.addEventListener("DOMContentLoaded", function () {
    if (typeof OrderIndexHandler !== "undefined") {
        window.orderIndexHandler = new OrderIndexHandler();
        window.orderIndexHandler.loadOrders();
    }
});
