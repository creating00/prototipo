export class DataTableActionsManager {
    constructor(tableId, actions) {
        this.tableId = tableId;
        this.actions = actions;
        this.table = document.getElementById(this.tableId);

        if (this.table) {
            this.setupListener();
        } else {
            console.error(`DataTable con ID '${tableId}' no encontrada.`);
        }
    }

    /**
     * Configura el listener de eventos en el documento (Delegación).
     */
    setupListener() {
        document.addEventListener("click", this.handleEvent.bind(this));
    }

    /**
     * Manejador general de clics.
     * @param {Event} e
     */
    handleEvent(e) {
        // Itera sobre las acciones para ver si el click coincide con un selector de botón
        for (const [selectorClass, callback] of Object.entries(this.actions)) {
            const button = e.target.closest(`.${selectorClass}`);

            if (button) {
                e.preventDefault();
                this.executeAction(button, callback);
                break; // Solo ejecuta una acción por click
            }
        }
    }

    /**
     * Ejecuta la función de callback pasando el elemento row.
     * @param {HTMLElement} button - El botón que fue clicado.
     * @param {Function} callback - La función a ejecutar con la fila.
     */
    executeAction(button, callback) {
        const row = button.closest("tr");
        if (!row || row.classList.contains("child") || row.querySelector("th"))
            return;

        // Ejecuta el callback pasando directamente el elemento row y el botón
        callback(row, button);
    }
}
