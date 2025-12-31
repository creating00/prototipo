export class DiscountForm {
    constructor() {
        this.typeSelect = document.getElementById("type");
        this.valueInput = document.getElementById("value"); // Campo del valor numérico
        this.maxAmountGroup = document.getElementById("max-amount-group");
        this.PERCENTAGE_VALUE = "2";

        if (this.typeSelect && this.valueInput) {
            this.init();
        }
    }

    init() {
        // Control de visibilidad del tope máximo
        this.typeSelect.addEventListener("change", () => {
            this.toggleMaxAmount();
            this.validateValue(); // Re-validar al cambiar el tipo
        });

        // Validación en tiempo real del valor
        this.valueInput.addEventListener("input", () => this.validateValue());

        // Ejecución inicial
        this.toggleMaxAmount();
    }

    toggleMaxAmount() {
        if (!this.maxAmountGroup) return;

        if (this.typeSelect.value === this.PERCENTAGE_VALUE) {
            this.maxAmountGroup.style.display = "block";
        } else {
            this.maxAmountGroup.style.display = "none";
        }
    }

    /**
     * Valida que si es porcentaje no exceda 100 y no sea negativo.
     */
    validateValue() {
        let value = parseFloat(this.valueInput.value);

        if (this.typeSelect.value === this.PERCENTAGE_VALUE) {
            if (value > 100) {
                this.valueInput.value = 100;
                this.showWarning("El porcentaje no puede ser mayor a 100%");
            }
        }

        if (value < 0) {
            this.valueInput.value = 0;
            this.showWarning("El valor no puede ser negativo");
        }
    }

    /**
     * Muestra un feedback visual rápido usando los estilos de Bootstrap que ya tienes
     */
    showWarning(message) {
        // Opcional: Podrías integrar SweetAlert2 o un Toast aquí
        console.warn(message);

        // Si tienes un contenedor de error en el componente, podrías activarlo:
        const errorFeedback = this.valueInput
            .closest(".compact-input-wrapper")
            .querySelector(".invalid-feedback");
        if (errorFeedback) {
            errorFeedback.innerText = message;
            errorFeedback.style.display = "block";
            setTimeout(() => (errorFeedback.style.display = "none"), 3000);
        }
    }
}
