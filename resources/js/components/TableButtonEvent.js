export class TableButtonEvent {
    constructor(tableId, config = {}) {
        this.tableId = tableId;
        this.table = document.getElementById(tableId);
        this.config = {
            csrfToken: document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content"),
            baseUrl: window.location.origin,
            buttonHandlers: {},
            ...config,
        };
        this.init();
    }

    init() {
        if (!this.table) {
            console.warn(`Tabla con ID "${this.tableId}" no encontrada`);
            return;
        }

        this.setupEventDelegation();
    }

    setupEventDelegation() {
        // Usar event delegation en toda la tabla
        this.table.addEventListener("click", (e) => {
            this.handleButtonClick(e);
        });
    }

    handleButtonClick(e) {
        // Buscar todos los handlers registrados
        Object.keys(this.config.buttonHandlers).forEach((buttonClass) => {
            if (e.target.closest(`.${buttonClass}`)) {
                e.preventDefault();
                const button = e.target.closest(`.${buttonClass}`);
                const row = button.closest("tr");
                const rowData = this.getRowData(row);

                // Ejecutar el handler correspondiente
                this.config.buttonHandlers[buttonClass](
                    rowData,
                    button,
                    row,
                    this
                );
            }
        });
    }

    getRowData(row) {
        const cells = row.querySelectorAll("td");
        const data = {};

        // Obtener datos de las celdas (excluyendo la última columna de acciones)
        const headers = Array.from(this.table.querySelectorAll("thead th"));

        headers.forEach((header, index) => {
            if (index < cells.length - 1) {
                // Excluir columna de acciones
                const headerText = header.textContent
                    .toLowerCase()
                    .replace(/\s+/g, "_");
                data[headerText] = cells[index].textContent;
            }
        });

        // Agregar información adicional útil
        data._rowElement = row;
        data._buttonElements = row.querySelectorAll("button, .btn");
        data._rowId = row.dataset.id || data.id;

        return data;
    }

    // Método para registrar handlers dinámicamente
    registerHandler(buttonClass, handler) {
        this.config.buttonHandlers[buttonClass] = handler;
    }

    // Método para remover handlers
    unregisterHandler(buttonClass) {
        delete this.config.buttonHandlers[buttonClass];
    }

    // ========== MÉTODOS ESPECÍFICOS PARA LARAVEL ==========

    /**
     * Navegar a una ruta de Laravel
     */
    navigateToRoute(routeName, parameters = {}) {
        const url = this.config.routeUrls?.[routeName];
        if (url) {
            // Reemplazar parámetros en la URL
            let finalUrl = url;
            Object.keys(parameters).forEach((key) => {
                finalUrl = finalUrl.replace(`{${key}}`, parameters[key]);
            });
            window.location.href = finalUrl;
        } else {
            console.warn(`Ruta "${routeName}" no configurada`);
        }
    }

    /**
     * Realizar una petición AJAX a una ruta de Laravel
     */
    async ajaxRequest(method, routeName, data = {}) {
        const url = this.config.routeUrls?.[routeName];
        if (!url) {
            throw new Error(`Ruta "${routeName}" no configurada`);
        }

        const options = {
            method: method.toUpperCase(),
            headers: {
                "X-CSRF-TOKEN": this.config.csrfToken,
                Accept: "application/json",
                "Content-Type": "application/json",
            },
        };

        if (method.toLowerCase() !== "get") {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Eliminar una fila mediante petición DELETE
     */
    async deleteRow(row, routeName, successCallback = null) {
        const rowData = this.getRowData(row);
        const rowId = rowData._rowId;

        if (!confirm(`¿Estás seguro de eliminar este registro?`)) {
            return;
        }

        try {
            const result = await this.ajaxRequest("delete", routeName, {
                id: rowId,
            });

            if (result.success) {
                // Eliminar fila con animación
                await this.removeRowWithAnimation(row);

                if (successCallback) {
                    successCallback(result, rowData);
                } else {
                    this.showToast(
                        "Registro eliminado correctamente",
                        "success"
                    );
                }
            } else {
                throw new Error(result.message || "Error al eliminar");
            }
        } catch (error) {
            console.error("Error al eliminar:", error);
            this.showToast("Error al eliminar el registro", "error");
        }
    }

    /**
     * Eliminar fila con animación
     */
    async removeRowWithAnimation(row) {
        row.style.transition = "all 0.3s ease";
        row.style.opacity = "0";
        row.style.height = "0";
        row.style.overflow = "hidden";

        await new Promise((resolve) => setTimeout(resolve, 300));
        row.remove();
    }

    /**
     * Mostrar notificación toast
     */
    showToast(message, type = "info") {
        // Puedes integrar con tu librería de toasts preferida
        alert(`${type.toUpperCase()}: ${message}`);
    }

    // ========== MÉTODOS PREDEFINIDOS PARA LARAVEL ==========

    static getDefaultHandlers(routeUrls = {}) {
        return {
            edit: (rowData, button, row, tableEvent) => {
                const rowId = rowData._rowId;
                if (routeUrls.edit) {
                    tableEvent.navigateToRoute("edit", { id: rowId });
                } else {
                    alert(`Editando: ${rowData.nombre || rowData.id}`);
                }
            },

            delete: (rowData, button, row, tableEvent) => {
                const rowId = rowData._rowId;
                if (routeUrls.delete) {
                    tableEvent.deleteRow(
                        row,
                        "delete",
                        (result, deletedData) => {
                            console.log("Registro eliminado:", deletedData);
                        }
                    );
                } else {
                    if (
                        confirm(
                            `¿Estás seguro de eliminar ${
                                rowData.nombre || "este registro"
                            }?`
                        )
                    ) {
                        alert(`Eliminando: ${rowData.nombre || rowData.id}`);
                    }
                }
            },

            view: (rowData, button, row, tableEvent) => {
                const rowId = rowData._rowId;
                if (routeUrls.view) {
                    tableEvent.navigateToRoute("view", { id: rowId });
                } else {
                    alert(`Viendo: ${rowData.nombre || rowData.id}`);
                }
            },
        };
    }

    /**
     * Configurar rutas de Laravel
     */
    setRoutes(routes) {
        this.config.routeUrls = routes;
    }
}
