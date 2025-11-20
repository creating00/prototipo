// order-form-handler.js
class OrderFormHandler {
    constructor(orderForm) {
        this.orderForm = orderForm;
    }

    // Configurar event listeners
    setupEventListeners() {
        // Event listeners para productos
        document.addEventListener("input", (e) => {
            if (
                e.target.classList.contains("product-select") ||
                e.target.classList.contains("quantity-input")
            ) {
                this.orderForm.products.updateCalculations();
            }
        });

        // Event listeners para datos de cliente
        const clientDocument = document.getElementById("client_document");
        const clientFullName = document.getElementById("client_full_name");

        if (clientDocument) {
            clientDocument.addEventListener("input", () =>
                this.updateSubmitButtonState()
            );
        }
        if (clientFullName) {
            clientFullName.addEventListener("input", () =>
                this.updateSubmitButtonState()
            );
        }

        // Envío del formulario
        const orderForm = document.getElementById("orderForm");
        if (orderForm) {
            orderForm.addEventListener("submit", (e) => this.handleSubmit(e));
        }

        // Inicializar estado del botón
        this.updateSubmitButtonState();
    }

    // Actualizar estado del botón de enviar
    updateSubmitButtonState() {
        const submitBtn = document.getElementById("submitOrderBtn");
        if (!submitBtn) return;

        const hasClient =
            this.orderForm.client.selectedClient !== null ||
            (document.getElementById("client_document") &&
                document.getElementById("client_document").value);

        const hasProducts = this.orderForm.products.addedProducts.length > 0;

        submitBtn.disabled = !hasClient || !hasProducts;
    }

    // Validar formulario completo
    validateForm() {
        if (!this.orderForm.products.validateProducts()) return false;
        if (!this.orderForm.client.validateClient()) return false;
        return true;
    }

    // Preparar datos del formulario
    prepareFormData() {
        return {
            productos: this.orderForm.products.getProductsData(),
            cliente: this.orderForm.client.getClientData(),
            id: window.authUserId,
        };
    }

    // Manejar envío del formulario
    async handleSubmit(e) {
        e.preventDefault();

        if (!this.validateForm()) return;

        const data = this.prepareFormData();

        try {
            const url = window.orderFormUrl;
            const method = window.orderFormMethod;

            await axios({ method, url, data });
            window.location.href = window.orderIndexUrl;
        } catch (error) {
            console.error("Error saving order:", error);
            alert(
                "Error al guardar el pedido: " +
                    (error.response?.data?.message || error.message)
            );
        }
    }
}
