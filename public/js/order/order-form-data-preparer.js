class OrderFormDataPreparer {
    constructor(orderForm) {
        this.orderForm = orderForm;
    }

    prepareFormData() {
        return {
            productos: this.orderForm.products.getProductsData(),
            cliente: this.orderForm.client.getClientData(),
            id: window.authUserId,
        };
    }

    getOrderTotal() {
        return this.orderForm.products.getTotalAmount();
    }

    isApplySaleChecked() {
        const applySaleCheckbox = document.getElementById("apply_sale");
        return applySaleCheckbox ? applySaleCheckbox.checked : false;
    }
}

window.OrderFormDataPreparer = OrderFormDataPreparer;
