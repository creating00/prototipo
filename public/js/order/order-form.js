// order-form.js
class OrderForm {
    constructor() {
        if (
            typeof OrderClient === "undefined" ||
            typeof OrderProducts === "undefined"
        ) {
            console.error("Faltan dependencias:", {
                OrderClient: typeof OrderClient,
                OrderProducts: typeof OrderProducts,
            });
            return;
        }

        try {
            this.client = new OrderClient(this);
            this.products = new OrderProducts(this);

            if (typeof OrderFormHandler !== "undefined") {
                this.handler = new OrderFormHandler(this);
            }
        } catch (error) {
            console.error("Error inicializando OrderForm:", error);
        }
    }

    async init() {
        if (!this.client || !this.products) {
            console.error("OrderForm no se inicializó correctamente");
            return;
        }

        await this.products.loadProducts();
        this.client.setupClientSearchModal();

        if (this.handler) {
            this.handler.setupEventListeners();
        }
    }

    async loadOrderData(orderId) {
        try {
            const response = await axios.get(`/api/orders/${orderId}`);
            const order = response.data;

            if (order.client) {
                this.client.selectClientFromModal(
                    order.client.id,
                    order.client.document,
                    order.client.full_name
                );
            }

            this.products.loadProductsFromOrder(order.items);
        } catch (error) {
            console.error("Error loading order:", error);
            alert("Error al cargar la orden");
        }
    }
}

window.OrderForm = OrderForm;
