class OrderPaymentProcessor {
    constructor(orderFormHandler) {
        this.orderFormHandler = orderFormHandler;
    }

    async createPayment(orderId, paymentType = 1) {
        try {
            const paymentData = {
                order_id: orderId,
                user_id: window.authUserId,
                payment_type: paymentType,
                amount: this.orderFormHandler.getOrderTotal(),
            };

            const response = await axios.post("/api/payments", paymentData);
            return response.data;
        } catch (error) {
            console.error("Error creating payment:", error);
            throw new Error(
                "Error al crear el pago: " +
                    (error.response?.data?.message || error.message)
            );
        }
    }

    async generateInvoice(paymentId) {
        try {
            const url = `/api/invoice/generate/${paymentId}`;
            window.open(url, "_blank");

            return { success: true, url };
        } catch (error) {
            console.error("Error generating invoice:", error);
            throw new Error(
                "Error al generar la factura: " +
                    (error.response?.data?.message || error.message)
            );
        }
    }
    
    async processCompleteSale(orderId, paymentType = 1) {
        try {
            const payment = await this.createPayment(orderId, paymentType);
            const invoice = await this.generateInvoice(payment.id);

            return { payment, invoice };
        } catch (error) {
            console.error("Error en processCompleteSale:", error);
            throw error;
        }
    }

    handleSaleError(error) {
        let errorMessage = error.message;

        if (
            errorMessage.includes("exceeds remaining balance") ||
            errorMessage.includes("saldo") ||
            errorMessage.includes("balance")
        ) {
            errorMessage =
                "Error: Saldo insuficiente para procesar el pago. La orden se creó pero el pago no pudo realizarse.";
        }

        return errorMessage;
    }
}

window.OrderPaymentProcessor = OrderPaymentProcessor;
