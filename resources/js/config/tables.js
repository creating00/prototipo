import { PRODUCT_MODAL_CONFIG } from "./datatables/product-modal-config.js";

export const TABLE_CONFIGS = {
    MAIN: {
        selector: ".datatable-main",
        options: {
            pageLength: 10,
            ordering: true,
        },
    },

    SMALL: {
        selector: ".datatable-sm",
        options: {
            pageLength: 5,
            ordering: false,
        },
    },

    SALES: {
        selector: ".datatable-sm-sales",
        options: {
            pageLength: 10,
            ordering: true,
            columnDefs: [
                {
                    targets: [3, 4], // Tipo y Pago
                    // Usamos una función de filtrado más sencilla
                    render: function (data, type, row) {
                        if (type === "filter") {
                            // Extrae el valor de data-search="X"
                            const match = data.match(/data-search="([^"]+)"/);
                            return match ? match[1] : data;
                        }
                        return data;
                    },
                },
                { targets: "_all", className: "dt-center" },
            ],
            initComplete: function () {
                // Pasamos la instancia correcta
                setupSalesFilters(this.api());
            },
        },
    },

    PRODUCT_MODAL: PRODUCT_MODAL_CONFIG,
};

function setupSalesFilters(api) {
    const filterTable = () => {
        // Los IDs de los Enums son números (1, 2...), el value del select es string "1", "2"
        const typeValue = document.getElementById("filter-type").value;
        const paymentValue = document.getElementById("filter-payment").value;
        console.log('Datos en columna 3:', api.column(3).data().toArray());

        // Limpiamos filtros anteriores y aplicamos nuevos
        // Usamos search(valor, isRegex, smartSearch)
        api.column(3).search(typeValue ? `^${typeValue}$` : "", true, false);
        api.column(4).search(
            paymentValue ? `^${paymentValue}$` : "",
            true,
            false,
        );

        api.draw();
    };

    const typeEl = document.getElementById("filter-type");
    const payEl = document.getElementById("filter-payment");

    typeEl?.addEventListener("change", filterTable);
    payEl?.addEventListener("change", filterTable);
}
