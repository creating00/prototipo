class DataTableInitializer {
    constructor(selector = "#tabla_data", options = {}) {
        this.selector = selector;
        this.defaultOptions = {
            responsive: true,
            order: [[6, 'desc']],
            language: {
                url: "/datatables/Spanish.json",
            },
        };
        this.options = { ...this.defaultOptions, ...options };
    }

    initialize() {
        if (
            typeof jQuery === "undefined" ||
            typeof jQuery.fn.DataTable === "undefined"
        ) {
            console.error("jQuery o DataTables no están cargados.");
            return;
        }

        $(this.selector).DataTable(this.options);
    }
}
