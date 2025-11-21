class OrderFormHandler {
    constructor(orderForm) {
        this.orderForm = orderForm;

        // Inicializar componentes
        this.validator = new OrderFormValidator(orderForm);
        this.dataPreparer = new OrderFormDataPreparer(orderForm);
        this.paymentProcessor = new OrderPaymentProcessor(this);
        this.eventHandler = new OrderFormEventHandler(this);
        this.submitHandler = new OrderFormSubmitHandler(this);
    }

    // Configurar event listeners
    setupEventListeners() {
        this.eventHandler.setupEventListeners();
    }

    // Actualizar estado del botón de enviar
    updateSubmitButtonState() {
        const submitBtn = document.getElementById("submitOrderBtn");
        if (!submitBtn) return;

        const { isValid } = this.validator.hasRequiredData();
        submitBtn.disabled = !isValid;
    }

    // Validar formulario completo
    validateForm() {
        return this.validator.validateForm();
    }

    // Preparar datos del formulario
    prepareFormData() {
        return this.dataPreparer.prepareFormData();
    }

    // Verificar si el checkbox "Aplicar venta" está activo
    isApplySaleChecked() {
        return this.dataPreparer.isApplySaleChecked();
    }

    // Obtener el total de la orden
    getOrderTotal() {
        return this.dataPreparer.getOrderTotal();
    }

    // Manejar envío del formulario
    async handleSubmit(e) {
        await this.submitHandler.handleSubmit(e);
    }
}

window.OrderFormHandler = OrderFormHandler;
