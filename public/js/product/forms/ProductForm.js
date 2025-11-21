class ProductForm {
    constructor(formId, mode = "create") {
        this.form = document.querySelector(`#${formId}`);
        this.mode = mode;
        this.redirectUrl = this.form?.dataset?.redirectUrl || "/admin/product";
        this.productId = this.form?.dataset?.productId || null;
        this.selectService = new SelectService();
        this.init();
    }

    async init() {
        if (this.form) {
            this.bindEvents();
            await this.loadSelects();
            if (this.mode === "edit") {
                await this.populateFormData();
            }
        }
    }

    bindEvents() {
        this.form.addEventListener("submit", this.handleSubmit.bind(this));

        // Evento para mostrar nombre de archivo en input file
        const fileInput = this.form.querySelector("#image");
        if (fileInput) {
            fileInput.addEventListener(
                "change",
                this.handleFileSelect.bind(this)
            );
        }
    }

    handleFileSelect(event) {
        const fileInput = event.target;
        const fileName = fileInput.files[0]?.name || "Seleccionar archivo";
        const label = fileInput.nextElementSibling;
        if (label) {
            label.textContent = fileName;
        }
    }

    async loadSelects() {
        try {
            const { branches, categories } =
                await SelectService.loadAllSelects();

            SelectService.populateSelect(
                this.form.querySelector("#branch_id"),
                branches,
                "Seleccione una sucursal"
            );

            SelectService.populateSelect(
                this.form.querySelector("#category_id"),
                categories,
                "Seleccione una categoría"
            );
        } catch (error) {
            console.error("Error loading selects:", error);
            this.showError("Error al cargar las opciones");
        }
    }

    async populateFormData() {
        try {
            if (this.productId) {
                this.currentProductData = await ProductService.getById(
                    this.productId
                );

                this.fillFormFields(this.currentProductData);
                this.setSelectValues();
            }
        } catch (error) {
            console.error("Error loading product data:", error);
            this.showError("Error al cargar los datos del producto");
        }
    }

    setSelectValues() {
        const branchSelect = this.form.querySelector("#branch_id");
        const categorySelect = this.form.querySelector("#category_id");

        if (branchSelect && this.currentProductData?.branch_id) {
            branchSelect.value = this.currentProductData.branch_id;
        }

        if (categorySelect && this.currentProductData?.category_id) {
            categorySelect.value = this.currentProductData.category_id;
        }
    }

    fillFormFields(productData) {
        const fields = [
            "code",
            "name",
            "description",
            "stock",
            "purchase_price",
            "sale_price",
        ];

        fields.forEach((field) => {
            const element = this.form.querySelector(`#${field}`);
            if (element && productData[field] !== undefined) {
                element.value = productData[field];
            }
        });

        // Manejar imagen si existe
        const imageInput = this.form.querySelector("#image");
        const imageLabel = imageInput?.nextElementSibling;
        if (productData.image && imageLabel) {
            imageLabel.textContent = productData.image;
        }
    }

    async handleSubmit(event) {
        event.preventDefault();

        const validationErrors = this.validateForm();
        if (validationErrors.length > 0) {
            this.showError(validationErrors.join("\n"));
            return;
        }

        try {
            await this.submitForm();
        } catch (error) {
            console.error("Error submitting form:", error);
            this.showError(
                "Error al procesar la solicitud: " +
                    (error.response?.data?.message || error.message)
            );
        }
    }

    validateForm() {
        const requiredFields = [
            { selector: "#code", message: "El código es obligatorio" },
            { selector: "#name", message: "El nombre es obligatorio" },
            {
                selector: "#category_id",
                message: "La categoría es obligatoria",
            },
            { selector: "#branch_id", message: "La sucursal es obligatoria" },
            { selector: "#stock", message: "El stock es obligatorio" },
            {
                selector: "#purchase_price",
                message: "El precio de compra es obligatorio",
            },
            {
                selector: "#sale_price",
                message: "El precio de venta es obligatorio",
            },
        ];

        const numberFields = [
            {
                selector: "#stock",
                message: "El stock debe ser un número válido",
            },
            {
                selector: "#purchase_price",
                message: "El precio de compra debe ser un número válido",
            },
            {
                selector: "#sale_price",
                message: "El precio de venta debe ser un número válido",
            },
        ];

        const errors = [
            ...FormValidator.validateRequired(requiredFields),
            ...FormValidator.validateNumber(numberFields),
        ];

        return errors;
    }

    getFormData() {
        const formData = new FormData();
        const imageFile = this.form.querySelector("#image").files[0];

        formData.append("code", this.form.querySelector("#code").value);
        formData.append("name", this.form.querySelector("#name").value);
        formData.append(
            "category_id",
            this.form.querySelector("#category_id").value
        );
        formData.append(
            "description",
            this.form.querySelector("#description").value
        );
        formData.append("stock", this.form.querySelector("#stock").value);
        formData.append(
            "branch_id",
            this.form.querySelector("#branch_id").value
        );
        formData.append(
            "purchase_price",
            this.form.querySelector("#purchase_price").value
        );
        formData.append(
            "sale_price",
            this.form.querySelector("#sale_price").value
        );

        if (imageFile) {
            formData.append("image", imageFile);
        }

        return formData;
    }

    async submitForm() {
        const formData = this.getFormData();

        if (this.mode === "edit" && this.productId) {
            await ProductService.update(this.productId, formData);
            this.showSuccess("Producto actualizado exitosamente");
        } else {
            await ProductService.create(formData);
            this.showSuccess("Producto creado exitosamente");
        }

        setTimeout(() => {
            window.location.href = this.redirectUrl;
        }, 1500);
    }

    showError(message) {
        alert(message);
    }

    showSuccess(message) {
        alert(message);
    }
}
