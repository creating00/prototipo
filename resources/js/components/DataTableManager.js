export class DataTableManager {
    static instances = new Map();

    constructor(tableElement, config = {}) {
        this.table = tableElement;
        this.config = this.getDefaultConfig(config);
        this.dataTable = null;
        this.currentRequest = null; // Nueva propiedad para controlar la solicitud actual
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
            // Verificar si la configuración tiene ajax personalizado
            if (this.config.ajax && typeof this.config.ajax === "function") {
                this.wrapAjaxFunction();
            }

            this.dataTable = new DataTable(this.table, this.config);
            DataTableManager.instances.set(this.table, this);
            this.table.classList.add("dataTable-initialized");
        }
        return this;
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
        if (this.dataTable) {
            // Primero abortar cualquier solicitud en curso
            if (this.currentRequest && this.currentRequest.abort) {
                this.currentRequest.abort();
            }

            // Luego recargar
            this.dataTable.ajax.reload(null, false); // El segundo parámetro false evita resetear paginación
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
