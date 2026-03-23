export class DataTableManager {
    static instances = new Map();

    constructor(tableElement, config = {}) {
        this.table = tableElement;
        this.config = this.getDefaultConfig(config);
        this.dataTable = null;
        this.currentRequest = null;
        this.init();
    }

    getDefaultConfig(customConfig = {}) {
        const defaultConfig = {
            responsive: true,
            language: {
                url: "/vendor/datatables/lang/es-ES.json",
            },
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            columnDefs: [
                {
                    targets: "_all",
                    className: "dt-center",
                },
            ],
        };

        return { ...defaultConfig, ...customConfig };
    }

    init() {
        if (this.table && !DataTableManager.instances.has(this.table)) {
            if (this.config.ajax && typeof this.config.ajax === "function") {
                this.wrapAjaxFunction();
            }

            this.dataTable = new DataTable(this.table, this.config);

            // Agrega este listener para que el botón se actualice al redibujar la tabla
            this.dataTable.on("draw", () => {
                this.updateBulkActionsVisibility();
            });

            this.initSelectionEvents();

            DataTableManager.instances.set(this.table, this);
            this.table.classList.add("dataTable-initialized");
        }
        return this;
    }

    initSelectionEvents() {
        const selectAll = this.table.querySelector(".select-all-checkbox");
        if (!selectAll) return;

        // Evento para el checkbox maestro
        selectAll.addEventListener("change", (e) => {
            const checkboxes = this.table.querySelectorAll(".row-checkbox");
            checkboxes.forEach((cb) => (cb.checked = e.target.checked));
            this.updateBulkActionsVisibility();
        });

        // Evento para checkboxes individuales (delegación)
        this.table.querySelector("tbody").addEventListener("change", (e) => {
            if (e.target.classList.contains("row-checkbox")) {
                const total =
                    this.table.querySelectorAll(".row-checkbox").length;
                const checked = this.table.querySelectorAll(
                    ".row-checkbox:checked",
                ).length;

                selectAll.checked = total === checked;
                selectAll.indeterminate = checked > 0 && checked < total;

                this.updateBulkActionsVisibility();
            }
        });
    }

    updateBulkActionsVisibility() {
        const checkedCount = this.getSelectedIds().length;
        // Busca botones con el atributo data-bulk-target apuntando a esta tabla
        const bulkButtons = document.querySelectorAll(
            `[data-bulk-target="#${this.table.id}"]`,
        );

        bulkButtons.forEach((btn) => {
            if (checkedCount > 0) {
                btn.classList.remove("d-none");
            } else {
                btn.classList.add("d-none");
            }
        });
    }

    getSelectedIds() {
        const checkboxes = this.table.querySelectorAll(".row-checkbox:checked");
        return Array.from(checkboxes).map((cb) => cb.value);
    }

    wrapAjaxFunction() {
        const originalAjax = this.config.ajax;

        this.config.ajax = (data, callback, settings) => {
            // Si hay una solicitud en curso, abortarla
            if (this.currentRequest && this.currentRequest.abort) {
                this.currentRequest.abort();
            }

            // Crear un controlador de abort para fetch
            const controller = new AbortController();
            const signal = controller.signal;

            // Guardar la solicitud actual para poder abortarla
            this.currentRequest = {
                abort: () => controller.abort(),
                xhr: null,
            };

            // Ejecutar la función ajax original
            const result = originalAjax(data, callback, settings);

            // Si la función original devuelve un objeto con abort, usarlo
            if (result && typeof result.abort === "function") {
                this.currentRequest = result;
            }

            return this.currentRequest;
        };
    }

    reload() {
        if (!this.dataTable) return this;

        if (this.currentRequest?.abort) {
            try {
                this.currentRequest.abort();
            } catch (e) {}
        }

        try {
            const hasAjax = typeof this.dataTable.ajax?.reload === "function";
            if (hasAjax) {
                this.dataTable.ajax.reload(null, false);
            } else {
                window.location.reload();
            }
        } catch (e) {
            window.location.reload();
        }

        return this;
    }

    destroy() {
        // Abortar cualquier solicitud en curso
        if (this.currentRequest && this.currentRequest.abort) {
            this.currentRequest.abort();
        }

        if (this.dataTable) {
            this.dataTable.destroy();
            DataTableManager.instances.delete(this.table);
            this.table.classList.remove("dataTable-initialized");
        }
        return this;
    }

    static initAll(selector = ".datatable", config = {}) {
        const tables = document.querySelectorAll(selector);
        tables.forEach((table) => {
            new DataTableManager(table, config);
        });
    }

    static getInstance(tableElement) {
        return DataTableManager.instances.get(tableElement);
    }
}
