export class TableManager {
    static initTable(config) {
        if (!document.getElementById(config.tableId)) {
            console.warn(`Table with ID '${config.tableId}' not found`);
            return null;
        }

        const rowManager = this.initRowActions(config);
        this.initHeaderActions(config);

        return {
            rowManager,
            config,
        };
    }

    static initRowActions(config) {
        const rowActionsMap = Object.fromEntries(
            Object.entries(config.rowActions).map(
                ([key, { selector, handler }]) => [
                    selector.replace(".", ""),
                    handler,
                ]
            )
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
                        handler();
                    } else {
                        alert(message);
                    }
                });
            }
        );
    }
}
