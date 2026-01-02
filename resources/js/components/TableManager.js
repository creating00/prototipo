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
        const rowActions = config.rowActions ?? {};

        const rowActionsMap = Object.fromEntries(
            Object.entries(rowActions).map(([key, action]) => [
                action.selector.replace(".", ""),
                (row) => action.handler(row, config.baseUrl),
            ])
        );

        return new DataTableActionsManager(config.tableId, rowActionsMap);
    }

    static initHeaderActions(config) {
        const headerActions = config.headerActions ?? {};

        Object.values(headerActions).forEach(
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
