// choices-manager.js
export class ChoicesManager {
    static SELECTOR = ".js-choice";
    static DEFAULT_OPTIONS = {
        searchEnabled: true,
        itemSelectText: "",
    };

    constructor() {
        this.instances = new Map();
    }

    init() {
        const selectElements = document.querySelectorAll(
            ChoicesManager.SELECTOR
        );

        selectElements.forEach((element, index) => {
            try {
                const instance = new Choices(
                    element,
                    ChoicesManager.DEFAULT_OPTIONS
                );
                this.instances.set(element, instance);
            } catch (error) {
                console.error(
                    `Error initializing Choices for element ${index}:`,
                    error
                );
            }
        });

        return this.instances;
    }

    destroy() {
        this.instances.forEach((instance, element) => {
            try {
                instance.destroy();
            } catch (error) {
                console.error("Error destroying Choices instance:", error);
            }
        });
        this.instances.clear();
    }

    getInstance(element) {
        return this.instances.get(element);
    }

    // Método estático para inicialización rápida
    static init() {
        const choicesManager = new ChoicesManager();
        return choicesManager.init();
    }
}
