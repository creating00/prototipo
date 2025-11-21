class OrderFormValidator {
    constructor(orderForm) {
        this.orderForm = orderForm;
    }

    validateForm() {
        if (!this.validateProducts()) return false;
        if (!this.validateClient()) return false;
        return true;
    }

    validateProducts() {
        return this.orderForm.products.validateProducts();
    }

    validateClient() {
        return this.orderForm.client.validateClient();
    }

    hasRequiredData() {
        const hasClient =
            this.orderForm.client.selectedClient !== null ||
            (document.getElementById("client_document") &&
                document.getElementById("client_document").value);

        const hasProducts = this.orderForm.products.addedProducts.length > 0;

        return { hasClient, hasProducts, isValid: hasClient && hasProducts };
    }
}

window.OrderFormValidator = OrderFormValidator;
