export class ChoicesHelper {
    /**
     * @param {string|HTMLElement} select - ID del select o elemento DOM
     */
    constructor(select) {
        this.selectElement =
            typeof select === "string"
                ? document.getElementById(select)
                : select;
    }

    getInstance() {
        if (!this.selectElement) return null;

        const alpineElement = this.selectElement.closest("[x-data]");

        // Opción 1: Usando __x (propiedad interna de Alpine)
        if (alpineElement && alpineElement.__x) {
            return alpineElement.__x.$data.choices;
        }

        // Opción 2: Usando Alpine.$data (si Alpine está en modo debug o expone esto)
        if (typeof Alpine !== "undefined" && Alpine.$data) {
            const alpineData = Alpine.$data(alpineElement);
            if (alpineData && alpineData.choices) {
                return alpineData.choices;
            }
        }

        // Opción 3: Directamente desde el elemento select
        if (this.selectElement._choices) {
            return this.selectElement._choices;
        }

        // Opción 4: Buscar en el DOM
        const choicesContainer = this.selectElement.closest(".choices");
        if (choicesContainer && choicesContainer.choices) {
            return choicesContainer.choices;
        }

        return null;
    }

    addOption(value, label, selectNew = true) {
        if (!this.selectElement) return false;

        const choicesInstance = this.getInstance();

        if (!choicesInstance) {
            console.warn("No se pudo obtener la instancia de Choices");

            // Intentar una alternativa: buscar directamente en el DOM
            const choicesElement = document.querySelector(
                ".choices__list--dropdown"
            );
            if (!choicesElement) {
                return false;
            }

            // Crear opción manualmente como fallback
            const option = new Option(label, value, selectNew, selectNew);
            this.selectElement.appendChild(option);

            // Disparar eventos para notificar cambios
            this.selectElement.dispatchEvent(
                new Event("change", { bubbles: true })
            );
            this.selectElement.dispatchEvent(
                new Event("input", { bubbles: true })
            );

            return true;
        } else {
            try {
                const currentChoices = this.getCurrentChoices(choicesInstance);
                const newChoice = {
                    value: value,
                    label: label,
                    selected: selectNew,
                };

                // Filtrar para evitar duplicados
                const filteredChoices = currentChoices.filter(
                    (item) => item.value !== value
                );

                choicesInstance.setChoices(
                    [...filteredChoices, newChoice],
                    "value",
                    "label",
                    false // false = reemplazar todo
                );

                return true;
            } catch (error) {
                console.error("Error con setChoices:", error);

                // Fallback: usar addItem si está disponible
                if (choicesInstance.addItem) {
                    choicesInstance.addItem({ value: value, label: label });
                    if (selectNew) {
                        choicesInstance.setChoiceByValue(value);
                    }
                    return true;
                }

                // Último recurso: manual
                return this.addOptionManually(value, label, selectNew);
            }
        }
    }

    /**
     * Obtiene las opciones actuales de Choices
     */
    getCurrentChoices(choicesInstance) {
        try {
            // Esto devuelve array de objetos con value/label
            const values = choicesInstance.getValue();
            if (Array.isArray(values)) {
                return values;
            }
            return [];
        } catch (error) {
            return [];
        }
    }

    /**
     * Agrega opción manualmente
     */
    addOptionManually(value, label, selectNew = true) {
        const existingOption = this.selectElement.querySelector(
            `option[value="${value}"]`
        );
        if (existingOption) {
            if (selectNew) {
                existingOption.selected = true;
            }
            return true;
        }

        const option = new Option(label, value, selectNew, selectNew);
        this.selectElement.appendChild(option);

        this.selectElement.dispatchEvent(
            new Event("change", { bubbles: true })
        );
        this.selectElement.dispatchEvent(new Event("input", { bubbles: true }));

        return true;
    }
}
