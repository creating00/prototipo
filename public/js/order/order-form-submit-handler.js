class OrderFormSubmitHandler {
    debugger;
    constructor(orderFormHandler) {
        this.orderFormHandler = orderFormHandler;
        this.paymentProcessor = orderFormHandler.paymentProcessor;
        this.paymentModal = new PaymentModal(orderFormHandler);
    }

    async handleSubmit(e) {
        e.preventDefault();

        if (!this.orderFormHandler.validateForm()) return;

        const data = this.orderFormHandler.prepareFormData();
        const isApplySale = this.orderFormHandler.isApplySaleChecked();

        try {
            const order = await this.saveOrder(data);
           
            if (isApplySale) {
                await this.handleCompleteSale(order.id);
            } else {
                this.showSuccessMessage("Orden creada exitosamente");
            }

            this.redirectToOrderList();
        } catch (error) {
            this.handleSubmitError(error);
        }
    }

    async saveOrder(data) {
        const url = window.orderFormUrl;
        const method = window.orderFormMethod;

        const response = await axios({ method, url, data });
        return response.data;
    }

    async handleCompleteSale(orderId) {
        try {
            const paymentType = await this.showPaymentModal(orderId);

            if (paymentType !== null) {
                await this.paymentProcessor.processCompleteSale(
                    orderId,
                    paymentType
                );
                this.showSuccessMessage(
                    "¡Venta completada exitosamente! Se ha creado la orden, el pago y la factura."
                );
            } else {
                this.showWarningMessage(
                    "Orden creada exitosamente, pero no se procesó el pago (operación cancelada)."
                );
            }
        } catch (saleError) {
            console.error("Error en proceso de venta:", saleError);
            const errorMessage =
                this.paymentProcessor.handleSaleError(saleError);
            this.showWarningMessage(
                `Orden creada exitosamente, pero hubo un error en el proceso de venta: ${errorMessage}`
            );
        }
    }

    showPaymentModal(orderId) {
        return this.paymentModal.show(orderId);
    }

    showSuccessMessage(message) {
        alert(message);
    }

    showWarningMessage(message) {
        alert(message);
    }

    redirectToOrderList() {
        window.location.href = window.orderIndexUrl;
    }

    handleSubmitError(error) {
        console.error("Error saving order:", error);
        alert(
            "Error al guardar el pedido: " +
                (error.response?.data?.message || error.message)
        );
    }
}

window.OrderFormSubmitHandler = OrderFormSubmitHandler;
