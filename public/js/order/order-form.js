// order-form.js - versión segura
class OrderForm {
    constructor() {
        // Esperar a que todas las clases estén disponibles
        if (
            typeof OrderClient === "undefined" ||
            typeof OrderProducts === "undefined" ||
            typeof OrderFormHandler === "undefined"
        ) {
            console.error("Faltan dependencias:", {
                OrderClient: typeof OrderClient,
                OrderProducts: typeof OrderProducts,
                OrderFormHandler: typeof OrderFormHandler,
            });
            return;
        }

        try {
            this.client = new OrderClient(this);
            this.products = new OrderProducts(this);
            this.handler = new OrderFormHandler(this);
            console.log("OrderForm inicializado correctamente");
        } catch (error) {
            console.error("Error inicializando OrderForm:", error);
        }
    }

    async init() {
        if (!this.client || !this.products || !this.handler) {
            console.error("OrderForm no se inicializó correctamente");
            return;
        }

        await this.products.loadProducts();
        this.client.setupClientSearchModal();
        this.handler.setupEventListeners();
    }

    // Cargar datos de orden existente (para editar)
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

            // Cargar productos de la orden
            this.products.loadProductsFromOrder(order.items);
        } catch (error) {
            console.error("Error loading order:", error);
            alert("Error al cargar la orden");
        }
    }
}
