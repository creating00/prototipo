// resources/js/modules/products/ImageRemovalHandler.js
export class ImageRemovalHandler {
    constructor() {
        this.removeImageCheckbox = document.getElementById("removeImage");
        this.urlInput = document.getElementById("image_url");
        this.fileInput = document.querySelector('input[name="imageFile"]');
        this.toastElement = document.getElementById("toastWarning");
        this.isInitialized = false;
    }

    init() {
        if (!this.removeImageCheckbox || this.isInitialized) {
            return;
        }

        this.bindEvents();
        this.isInitialized = true;
    }

    bindEvents() {
        this.removeImageCheckbox.addEventListener("change", () =>
            this.handleCheckboxChange()
        );
    }

    handleCheckboxChange() {
        if (this.removeImageCheckbox.checked) {
            this.disableImageInputs();
            this.showToast();
        } else {
            this.enableImageInputs();
        }
    }

    disableImageInputs() {
        // Guardar el valor original antes de deshabilitar
        if (this.urlInput && !this.urlInput.dataset.originalValue) {
            this.urlInput.dataset.originalValue = this.urlInput.value;
        }

        if (this.urlInput) {
            this.urlInput.disabled = true;
            this.urlInput.value = "";
        }

        if (this.fileInput) {
            this.fileInput.disabled = true;
        }
    }

    enableImageInputs() {
        if (this.urlInput) {
            this.urlInput.disabled = false;

            // Restaurar valor original si existe
            if (this.urlInput.dataset.originalValue) {
                this.urlInput.value = this.urlInput.dataset.originalValue;
                delete this.urlInput.dataset.originalValue;
            }
        }

        if (this.fileInput) {
            this.fileInput.disabled = false;
        }
    }

    showToast() {
        if (!this.toastElement) {
            return;
        }

        const toast = bootstrap.Toast.getOrCreateInstance(this.toastElement);
        toast.show();
    }

    destroy() {
        if (this.removeImageCheckbox) {
            this.removeImageCheckbox.removeEventListener(
                "change",
                this.handleCheckboxChange
            );
        }
        this.isInitialized = false;
    }
}
