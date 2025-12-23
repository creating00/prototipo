// resources/js/helpers/OnlyNumbers.js
export class OnlyNumbers {
    /**
     * Constructor de la clase OnlyNumbers
     * @param {string|HTMLElement} selector - Selector CSS o elemento DOM
     * @param {Object} options - Opciones de configuración
     */
    constructor(selector, options = {}) {
        this.element =
            typeof selector === "string"
                ? document.querySelector(selector)
                : selector;

        this.options = {
            minLength: options.minLength || 7,
            maxLength: options.maxLength || 8,
            allowDecimals: options.allowDecimals || false,
            allowNegative: options.allowNegative || false,
            format: options.format || null, // 'document', 'phone', 'custom'
            customPattern: options.customPattern || null,
            onValid: options.onValid || null,
            onInvalid: options.onInvalid || null,
            ...options,
        };

        if (!this.element) {
            console.warn(`Elemento no encontrado: ${selector}`);
            return;
        }

        this.init();
    }

    /**
     * Inicializa los event listeners
     */
    init() {
        // Prevenir entrada de caracteres no numéricos
        this.element.addEventListener("input", this.handleInput.bind(this));

        // Validar en blur
        this.element.addEventListener("blur", this.validate.bind(this));

        // Permitir pegado con limpieza
        this.element.addEventListener("paste", this.handlePaste.bind(this));

        // Inicializar con el valor actual
        this.cleanValue();
    }

    /**
     * Maneja el evento input
     * @param {Event} event
     */
    handleInput(event) {
        let value = event.target.value;

        // Limpiar según las opciones
        value = this.cleanString(value);

        // Aplicar formato si está configurado
        if (this.options.format) {
            value = this.applyFormat(value);
        }

        event.target.value = value;
    }

    /**
     * Maneja el evento paste
     * @param {Event} event
     */
    handlePaste(event) {
        event.preventDefault();
        const pastedText = (
            event.clipboardData || window.clipboardData
        ).getData("text");
        const cleanedText = this.cleanString(pastedText);

        // Insertar el texto limpio
        const start = event.target.selectionStart;
        const end = event.target.selectionEnd;
        const currentValue = event.target.value;

        event.target.value =
            currentValue.substring(0, start) +
            cleanedText +
            currentValue.substring(end);

        // Mover el cursor
        event.target.setSelectionRange(
            start + cleanedText.length,
            start + cleanedText.length
        );

        // Disparar evento input
        event.target.dispatchEvent(new Event("input", { bubbles: true }));
    }

    /**
     * Limpia la cadena según las opciones
     * @param {string} str - Cadena a limpiar
     * @returns {string} Cadena limpia
     */
    cleanString(str) {
        let pattern = "\\d"; // Solo dígitos por defecto

        if (this.options.allowDecimals) {
            pattern = "[\\d\\.]";
        }

        if (this.options.allowNegative) {
            pattern = "-?" + pattern;
        }

        const regex = new RegExp(pattern, "g");
        const matches = str.match(regex);

        return matches ? matches.join("") : "";
    }

    /**
     * Aplica formato al valor
     * @param {string} value - Valor a formatear
     * @returns {string} Valor formateado
     */
    applyFormat(value) {
        const cleanValue = value.replace(/\D/g, "");

        switch (this.options.format) {
            case "document":
                // Formato para documentos (ej: 12.345.678)
                if (cleanValue.length === 8) {
                    return cleanValue.replace(
                        /(\d{2})(\d{3})(\d{3})/,
                        "$1.$2.$3"
                    );
                } else if (cleanValue.length === 7) {
                    return cleanValue.replace(
                        /(\d{1})(\d{3})(\d{3})/,
                        "$1.$2.$3"
                    );
                }
                break;

            case "phone":
                // Formato para teléfonos (ej: +54 11 1234-5678)
                if (cleanValue.length === 10) {
                    return cleanValue.replace(
                        /(\d{2})(\d{2})(\d{4})(\d{4})/,
                        "+$1 $2 $3-$4"
                    );
                }
                break;

            case "custom":
                if (this.options.customPattern) {
                    return this.formatWithPattern(
                        cleanValue,
                        this.options.customPattern
                    );
                }
                break;
        }

        return value;
    }

    /**
     * Formatea con un patrón personalizado
     * @param {string} value - Valor a formatear
     * @param {string} pattern - Patrón (ej: '###.###.###')
     * @returns {string} Valor formateado
     */
    formatWithPattern(value, pattern) {
        let result = "";
        let valueIndex = 0;

        for (let i = 0; i < pattern.length && valueIndex < value.length; i++) {
            if (pattern[i] === "#") {
                result += value[valueIndex];
                valueIndex++;
            } else {
                result += pattern[i];
            }
        }

        // Agregar los dígitos restantes sin formato
        if (valueIndex < value.length) {
            result += value.substring(valueIndex);
        }

        return result;
    }

    /**
     * Valida el valor actual
     */
    validate() {
        const value = this.element.value;
        const cleanValue = this.cleanString(value);

        let isValid = true;
        let message = "";

        // Validar longitud mínima
        if (cleanValue.length < this.options.minLength) {
            isValid = false;
            message = `Mínimo ${this.options.minLength} dígitos requeridos`;
        }

        // Validar longitud máxima
        if (cleanValue.length > this.options.maxLength) {
            isValid = false;
            message = `Máximo ${this.options.maxLength} dígitos permitidos`;
        }

        // Validación personalizada
        if (this.options.customValidator) {
            const customResult = this.options.customValidator(cleanValue);
            if (customResult !== true) {
                isValid = false;
                message = customResult.message || "Validación fallida";
            }
        }

        // Aplicar clases CSS
        this.element.classList.toggle(
            "is-valid",
            isValid && cleanValue.length > 0
        );
        this.element.classList.toggle(
            "is-invalid",
            !isValid && cleanValue.length > 0
        );

        // Mostrar mensaje de error
        this.showError(message);

        // Callbacks
        if (isValid && this.options.onValid) {
            this.options.onValid(cleanValue);
        } else if (!isValid && this.options.onInvalid) {
            this.options.onInvalid(cleanValue, message);
        }

        return isValid;
    }

    /**
     * Muestra u oculta el mensaje de error
     * @param {string} message - Mensaje de error
     */
    showError(message) {
        // Buscar contenedor de error existente o crear uno
        let errorElement = this.element.parentElement.querySelector(
            ".only-numbers-error"
        );

        if (message) {
            if (!errorElement) {
                errorElement = document.createElement("div");
                errorElement.className =
                    "only-numbers-error invalid-feedback d-block mt-1";
                this.element.parentElement.appendChild(errorElement);
            }
            errorElement.textContent = message;
            errorElement.style.display = "block";
        } else if (errorElement) {
            errorElement.style.display = "none";
        }
    }

    /**
     * Limpia el valor actual
     */
    cleanValue() {
        this.element.value = this.cleanString(this.element.value);
        this.validate();
    }

    /**
     * Obtiene el valor limpio (solo números)
     * @returns {string} Valor limpio
     */
    getCleanValue() {
        return this.cleanString(this.element.value);
    }

    /**
     * Destruye la instancia y limpia los event listeners
     */
    destroy() {
        this.element.removeEventListener("input", this.handleInput);
        this.element.removeEventListener("blur", this.validate);
        this.element.removeEventListener("paste", this.handlePaste);

        // Remover clases
        this.element.classList.remove("is-valid", "is-invalid");

        // Remover mensaje de error
        const errorElement = this.element.parentElement.querySelector(
            ".only-numbers-error"
        );
        if (errorElement) {
            errorElement.remove();
        }
    }
}

/**
 * Función helper para inicializar rápidamente
 * @param {string} selector - Selector CSS
 * @param {Object} options - Opciones
 * @returns {OnlyNumbers} Instancia creada
 */
export function initOnlyNumbers(selector, options = {}) {
    return new OnlyNumbers(selector, options);
}
