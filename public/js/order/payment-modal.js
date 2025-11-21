class PaymentModal {
    constructor(orderFormHandler) {
        this.orderFormHandler = orderFormHandler;
        this.modalElement = document.getElementById("paymentModal");
        this.modal = null;
        this.resolvePromise = null;
    }

    show(orderId) {
        return new Promise((resolve) => {
            this.resolvePromise = resolve;

            const orderTotal = this.orderFormHandler.getOrderTotal();
            const clientName = this.getCurrentClientName();

            this.setupModalData(orderId, orderTotal, clientName);
            this.setupEventListeners();

            this.modal = new bootstrap.Modal(this.modalElement);
            this.modal.show();
        });
    }

    getCurrentClientName() {
        const clientFullName = document.getElementById("client_full_name");
        return clientFullName
            ? clientFullName.value
            : "Cliente no seleccionado";
    }

    setupModalData(orderId, orderTotal, clientName) {
        document.getElementById("modalOrderId").textContent = orderId;
        document.getElementById(
            "modalOrderTotal"
        ).textContent = `$${orderTotal.toFixed(2)}`;
        document.getElementById("modalClientName").textContent = clientName;

        // Resetear campos
        document.getElementById("paymentType").value = "";
        document.getElementById("paymentAmount").value = "";
        this.hideAllPaymentDetails();
    }

    setupEventListeners() {
        this.removeEventListeners();

        document
            .getElementById("confirmPayment")
            .addEventListener("click", () => this.handleConfirm());
        document
            .getElementById("paymentType")
            .addEventListener("change", (e) =>
                this.handlePaymentTypeChange(e.target.value)
            );
        this.modalElement.addEventListener("hidden.bs.modal", () =>
            this.handleModalClose()
        );
    }

    removeEventListeners() {
        // Clonar elementos para remover event listeners previos
        const confirmBtn = document.getElementById("confirmPayment");
        const paymentTypeSelect = document.getElementById("paymentType");

        confirmBtn.replaceWith(confirmBtn.cloneNode(true));
        paymentTypeSelect.replaceWith(paymentTypeSelect.cloneNode(true));
    }

    handleConfirm() {
        const paymentType = document.getElementById("paymentType").value;
        const paymentAmount = document.getElementById("paymentAmount").value;

        if (!paymentType) {
            alert("Por favor seleccione un tipo de pago");
            return;
        }

        if (paymentAmount && parseFloat(paymentAmount) <= 0) {
            alert("El monto debe ser mayor a 0");
            return;
        }

        this.modal.hide();
        this.resolvePromise(parseInt(paymentType));
    }

    handlePaymentTypeChange(paymentType) {
        this.hideAllPaymentDetails();
        const amountField = document.getElementById("amountField");
        const paymentDetails = document.getElementById("paymentDetails");

        if (paymentType === "1") {
            // Cash
            amountField.style.display = "block";
        } else {
            amountField.style.display = "none";
        }

        switch (paymentType) {
            case "2": // Card (crédito/débito)
            case "3": // Transfer
                document.getElementById("cardFields").style.display = "block";
                paymentDetails.style.display = "block";
                break;
            case "4": // Check
                document.getElementById("checkFields").style.display = "block";
                paymentDetails.style.display = "block";
                break;
        }
    }

    hideAllPaymentDetails() {
        document.getElementById("cardFields").style.display = "none";
        document.getElementById("transferFields").style.display = "none";
        document.getElementById("checkFields").style.display = "none";
        document.getElementById("paymentDetails").style.display = "none";
    }

    handleModalClose() {
        if (this.resolvePromise) {
            this.resolvePromise(null);
        }
    }
}

window.PaymentModal = PaymentModal;
