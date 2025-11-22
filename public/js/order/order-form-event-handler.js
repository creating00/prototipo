class OrderFormEventHandler {
    constructor(orderFormHandler) {
        this.orderFormHandler = orderFormHandler;
    }

    setupEventListeners() {
        this.setupProductEventListeners();
        this.setupClientEventListeners();
        this.setupFormSubmitListener();
        this.orderFormHandler.updateSubmitButtonState();
    }

    setupProductEventListeners() {
        document.addEventListener("input", (e) => {
            if (
                e.target.classList.contains("product-select") ||
                e.target.classList.contains("quantity-input")
            ) {
                this.orderFormHandler.orderForm.products.updateCalculations();
                this.orderFormHandler.updateSubmitButtonState();
            }
        });
    }

    setupClientEventListeners() {
        const clientDocument = document.getElementById("client_document");
        const clientFullName = document.getElementById("client_full_name");

        if (clientDocument) {
            clientDocument.addEventListener("input", () =>
                this.orderFormHandler.updateSubmitButtonState()
            );
        }
        if (clientFullName) {
            clientFullName.addEventListener("input", () =>
                this.orderFormHandler.updateSubmitButtonState()
            );
        }
    }

    setupFormSubmitListener() {
        //console.log("Listener registrado");

        const orderForm = document.getElementById("orderForm");
        if (orderForm) {
            orderForm.addEventListener("submit", (e) =>
                this.orderFormHandler.handleSubmit(e)
            );
        }
    }
}

window.OrderFormEventHandler = OrderFormEventHandler;
