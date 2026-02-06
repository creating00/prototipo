import { initMonthPicker } from "../utils/ui-components";

// --- HELPERS PRIVADOS ---

const formatCurrency = (val) =>
    val.toLocaleString("es-AR", { minimumFractionDigits: 2 });

/**
 * Inicializa los componentes compartidos (MonthPicker y Botón Reset)
 */
function setupCommonUI(filterCallback, resetIds) {
    // Inicializar picker de mes
    initMonthPicker("filter-month", filterCallback);

    // Listener para el botón Reset
    document
        .getElementById("btn-reset-filters")
        ?.addEventListener("click", () => {
            resetIds.forEach((id) => {
                const el = document.getElementById(id);
                if (!el) return;
                if (el.type === "checkbox") el.checked = false;
                else el.value = "";
            });

            const monthEl = document.getElementById("filter-month");
            if (monthEl?._flatpickr) monthEl._flatpickr.clear();

            filterCallback();
        });
}

/**
 * Lógica genérica para actualizar el footer
 */
function updateGenericFooter(api, dataMap) {
    let totals = { ars: 0, usd: 0 };
    api.rows({ search: "applied" }).every(function () {
        dataMap(this.node(), totals);
    });

    const elArs = document.getElementById("total-ars");
    const elUsd = document.getElementById("total-usd");
    if (elArs) elArs.textContent = `$ ${formatCurrency(totals.ars)}`;
    if (elUsd) elUsd.textContent = `U$D ${formatCurrency(totals.usd)}`;
}

// --- FUNCIONES EXPORTADAS ---

export function setupSalesFilters(api) {
    const filterTable = () => {
        const type = document.getElementById("filter-type")?.value;
        const payment = document.getElementById("filter-payment")?.value;
        const invoice = document.getElementById("filter-invoice")?.checked;
        const month = document.getElementById("filter-month")?.value;

        api.column(3).search(type ? `^${type}$` : "", {
            regex: true,
            smart: false,
        });
        api.column(4).search(payment ? `\\b${payment}\\b` : "", {
            regex: true,
            smart: false,
        });

        DataTable.ext.search.push((settings, data, dataIndex) => {
            if (settings.nTable.classList.contains("datatable-sm-sales")) {
                const ds = api.row(dataIndex).node()?.dataset;
                if (!ds) return true;
                const reqInv =
                    ds.requires_invoice_raw === "1" ||
                    ds.requires_invoice_raw === "true";
                if (invoice && !reqInv) return false;
                if (month && ds.created_at && !ds.created_at.startsWith(month))
                    return false;
            }
            return true;
        });
        api.draw();
        DataTable.ext.search.pop();
    };

    const ids = ["filter-type", "filter-payment", "filter-invoice"];
    setupCommonUI(filterTable, ids);
    ids.forEach((id) =>
        document.getElementById(id)?.addEventListener("change", filterTable),
    );
}

export function setupExpenseFilters(api) {
    const filterTable = () => {
        const payment = document.getElementById("filter-payment")?.value;
        const month = document.getElementById("filter-month")?.value;
        const branch = document.getElementById("filter-branch")?.value;

        // Filtro por forma de pago (columna)
        api.column(5).search(payment ? `^${payment}$` : "", {
            regex: true,
            smart: false,
        });

        DataTable.ext.search.push((settings, data, dataIndex) => {
            if (!settings.nTable.classList.contains("datatable-sm-expenses")) {
                return true;
            }

            const row = api.row(dataIndex).node();
            if (!row) return true;

            const ds = row.dataset;

            // --- filtro por mes ---
            if (month && ds.date) {
                const [d, m, y] = ds.date.split("/");
                if (`${y}-${m}` !== month) return false;
            }

            // --- filtro por sucursal ---
            if (branch && ds.branchId !== branch) {
                return false;
            }

            return true;
        });

        api.draw();
        DataTable.ext.search.pop();
    };

    setupCommonUI(filterTable, ["filter-payment", "filter-branch"]);

    ["filter-payment", "filter-branch"].forEach((id) =>
        document.getElementById(id)?.addEventListener("change", filterTable),
    );
}

export function updateSalesFooter(api) {
    updateGenericFooter(api, (row, totals) => {
        totals.ars += Number(row.getAttribute("data-total_ars") || 0);
        totals.usd += Number(row.getAttribute("data-total_usd") || 0);
    });
}

export function updateExpenseFooter(api) {
    updateGenericFooter(api, (row, totals) => {
        const currency = row.getAttribute("data-currency");
        const amount = Number(row.getAttribute("data-amount_raw") || 0);
        if (currency === "1") totals.ars += amount;
        else if (currency === "2") totals.usd += amount;
    });
}
