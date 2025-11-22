class OrderIndexHandler {
    constructor() {
        this.tableBody = document.querySelector("#tableOrders tbody");
        this.rowBuilder = new OrderRowBuilder(this);
        this.paymentProcessor = new OrderPaymentProcessor({
            getOrderTotal: () => 0,
        });
        this.dataTable = null;
    }

    async loadOrders() {
        try {
            const res = await axios.get("/api/orders");
            const rows = res.data.map((order) =>
                this.rowBuilder.buildRow(order)
            );
            this.tableBody.innerHTML = rows.join("");

            // Si DataTables ya está inicializado, destruirlo y recrearlo
            if (this.dataTable) {
                this.dataTable.destroy();
            }

            return res.data; // Retornar los datos para poder usar .then()
        } catch (error) {
            console.error("Error loading orders:", error);
            alert("Error al cargar los pedidos");
            throw error;
        }
    }

    async createPayment(orderId) {
        try {
            const paymentModal = new PaymentModal(
                {
                    getOrderTotal: () => {
                        const row = document.querySelector(
                            `tr:has(button[onclick="orderIndexHandler.createPayment(${orderId})"])`
                        );
                        const amountText =
                            row.querySelector("td:nth-child(5)").textContent;
                        return parseFloat(amountText.replace("S/. ", ""));
                    },
                },
                () => {
                    const row = document.querySelector(
                        `tr:has(button[onclick="orderIndexHandler.createPayment(${orderId})"])`
                    );
                    const clientName = row.querySelector(
                        "td:nth-child(2) strong"
                    ).textContent;
                    return clientName;
                }
            );

            const paymentType = await paymentModal.show(orderId);

            if (paymentType !== null) {
                const paymentProcessor = new OrderPaymentProcessor({
                    getOrderTotal: () => {
                        const row = document.querySelector(
                            `tr:has(button[onclick="orderIndexHandler.createPayment(${orderId})"])`
                        );
                        const amountText =
                            row.querySelector("td:nth-child(5)").textContent;
                        return parseFloat(amountText.replace("S/. ", ""));
                    },
                });

                await paymentProcessor.processCompleteSale(
                    orderId,
                    paymentType
                );
                alert("¡Pago procesado exitosamente! Se generó la factura.");
                this.loadOrders();
            }
        } catch (error) {
            console.error("Error creating payment:", error);
            alert("Error al procesar el pago: " + error.message);
        }
    }

    async deleteOrder(id) {
        if (!confirm("¿Estás seguro de eliminar este pedido?")) return;

        try {
            await axios.delete(`/api/orders/${id}`);
            this.loadOrders();
        } catch (error) {
            console.error("Error deleting order:", error);
            alert("Error al eliminar el pedido");
        }
    }

    viewInvoice(paymentId) {
        this.paymentProcessor.generateInvoice(paymentId);
    }
}

window.OrderIndexHandler = OrderIndexHandler;
