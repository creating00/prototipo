import { PRODUCT_MODAL_CONFIG } from "./datatables/product-modal-config.js";
import flatpickr from "flatpickr";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index.js";
import { Spanish } from "flatpickr/dist/l10n/es.js";

// Importar estilos (puedes hacerlo en tu CSS/SASS también)
import "flatpickr/dist/flatpickr.css";
import "flatpickr/dist/plugins/monthSelect/style.css";

function initMonthPicker(callback) {
    const monthInput = document.getElementById("filter-month");

    if (monthInput) {
        flatpickr(monthInput, {
            locale: Spanish,
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m", // Lo que se guarda en el value
                    altFormat: "F Y", // Lo que ve el usuario
                }),
            ],
            // Importante: Flatpickr no dispara 'change' nativo al elegir
            onChange: function (selectedDates, dateStr) {
                monthInput.value = dateStr;
                callback(); // Llamamos a la función de filtrado
            },
        });
    }
}

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
                    targets: [3, 4], // Columna 4 es Pagos
                    render: function (data, type) {
                        if (type === "filter") {
                            // Buscamos globalmente todos los data-search="x"
                            const matches = [
                                ...data.matchAll(/data-search="([^"]+)"/g),
                            ];
                            if (matches.length > 0) {
                                // Unimos todos los IDs encontrados: "1 3"
                                return matches.map((m) => m[1]).join(" ");
                            }
                            return data;
                        }
                        return data;
                    },
                },
                { targets: "_all", className: "dt-center" },
            ],

            initComplete: function () {
                const api = this.api();
                setupSalesFilters(api);
                updateSalesFooter(api);
            },

            drawCallback: function () {
                const api = this.api();
                updateSalesFooter(api);
            },
        },
    },

    PRODUCT_MODAL: PRODUCT_MODAL_CONFIG,
};

function setupSalesFilters(api) {
    const filterTable = () => {
        const typeValue = document.getElementById("filter-type")?.value;
        const paymentValue = document.getElementById("filter-payment")?.value;
        const invoiceChecked =
            document.getElementById("filter-invoice")?.checked;
        const monthValue = document.getElementById("filter-month")?.value;

        // 1. Búsqueda exacta en columnas (3: Tipo, 5: Pago)
        api.column(3).search(typeValue ? `^${typeValue}$` : "", {
            regex: true,
            smart: false,
        });

        api.column(4).search(paymentValue ? `\\b${paymentValue}\\b` : "", {
            regex: true,
            smart: false,
        });

        // 2. Filtro personalizado (Facturación y Fecha)
        DataTable.ext.search.push((settings, data, dataIndex) => {
            if (settings.nTable.classList.contains("datatable-sm-sales")) {
                const rowNode = api.row(dataIndex).node();
                if (!rowNode) return true;

                const ds = rowNode.dataset;
                const requiresInvoice =
                    ds.requires_invoice_raw === "1" ||
                    ds.requires_invoice_raw === "true";
                const dateRaw = ds.created_at;

                if (invoiceChecked && !requiresInvoice) return false;
                if (monthValue && dateRaw && !dateRaw.startsWith(monthValue))
                    return false;
            }
            return true;
        });

        api.draw();
        DataTable.ext.search.pop();
    };

    // Inicializar Flatpickr pasando la función de filtrado
    initMonthPicker(filterTable);

    // Listener para el botón Reset
    document
        .getElementById("btn-reset-filters")
        ?.addEventListener("click", function () {
            // Reseteo de valores
            const typeEl = document.getElementById("filter-type");
            const paymentEl = document.getElementById("filter-payment");
            const invoiceEl = document.getElementById("filter-invoice");
            const monthEl = document.getElementById("filter-month");

            if (typeEl) typeEl.value = "";
            if (paymentEl) paymentEl.value = "";
            if (invoiceEl) invoiceEl.checked = false;
            if (monthEl && monthEl._flatpickr) monthEl._flatpickr.clear();

            // Aplicar cambios a la tabla
            filterTable();
        });

    // Listeners para cambios inmediatos en los filtros
    ["filter-type", "filter-payment", "filter-invoice"].forEach((id) => {
        document.getElementById(id)?.addEventListener("change", filterTable);
    });
}

function updateSalesFooter(api) {
    let totalARS = 0;
    let totalUSD = 0;

    api.rows({ search: "applied" }).every(function () {
        // Obtenemos el elemento <tr> de la fila actual
        const rowNode = this.node();

        // Leemos los atributos data- que imprimiste en el HTML
        const ars = rowNode.getAttribute("data-total_ars");
        const usd = rowNode.getAttribute("data-total_usd");

        totalARS += Number(ars || 0);
        totalUSD += Number(usd || 0);
    });

    const elArs = document.getElementById("total-ars");
    const elUsd = document.getElementById("total-usd");

    if (elArs) {
        elArs.textContent =
            "$ " +
            totalARS.toLocaleString("es-AR", { minimumFractionDigits: 2 });
    }
    if (elUsd) {
        elUsd.textContent =
            "U$D " +
            totalUSD.toLocaleString("es-AR", { minimumFractionDigits: 2 });
    }
}
