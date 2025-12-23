export class TableManager {
    static initTable(config) {
        const tableElement = document.getElementById(config.tableId);

        if (!tableElement) {
            console.warn(`Table with ID '${config.tableId}' not found`);
            return null;
        }

        // Obtener la base URL del componente Blade ---
        const container = tableElement.closest("[data-base-url]");
        const baseUrl = container ? container.dataset.baseUrl : "";

        config.baseUrl = baseUrl;

        const rowManager = this.initRowActions(config);
        this.initHeaderActions(config);

        return {
            rowManager,
            config,
        };
    }

    static initRowActions(config) {
        const rowActionsMap = Object.fromEntries(
            Object.entries(config.rowActions).map(([key, action]) => [
                action.selector.replace(".", ""),
                // Pasamos el config.baseUrl al handler si el módulo lo necesita
                (row) => action.handler(row, config.baseUrl),
            ])
        );
        return new DataTableActionsManager(config.tableId, rowActionsMap);
    }

    static initHeaderActions(config) {
        Object.values(config.headerActions).forEach(
            ({ selector, message, handler }) => {
                const button = document.querySelector(selector);
                button?.addEventListener("click", (e) => {
                    e.preventDefault();
                    if (handler) {
                        handler(config.baseUrl);
                    } else {
                        alert(message);
                    }
                });
            }
        );
    }
}
