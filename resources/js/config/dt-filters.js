import { initMonthPicker } from "../utils/ui-components";

// --- HELPERS PRIVADOS ---

const formatCurrency = (val) =>
    val.toLocaleString("es-AR", { minimumFractionDigits: 2 });

/**
 * Inicializa los componentes compartidos (MonthPicker y Botón Reset)
 */
function setupCommonUI(filterCallback, resetIds) {
    const modeEl = document.getElementById("filter-date-mode");
    const getMode = () => modeEl?.value || "month";

    const initPicker = () => {
        initMonthPicker("filter-month", filterCallback, getMode());
    };

    // Inicializar picker con el modo inicial
    initPicker();

    // Escuchar cambios de modo de fecha (Mes / Día)
    modeEl?.addEventListener("change", () => {
        const monthEl = document.getElementById("filter-month");
        if (monthEl) {
            monthEl.value = "";
            if (monthEl._flatpickr) {
                monthEl._flatpickr.clear();
            }
        }
        initPicker();
        filterCallback();
    });

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

            // Forzar volver a modo 'Mes' en el reset
            if (modeEl) {
                modeEl.value = "month";
            }

            const monthEl = document.getElementById("filter-month");
            if (monthEl) {
                monthEl.value = "";
            }

            initPicker();
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
    const filterPayment = document.getElementById("filter-payment");
    const filterBankAccount = document.getElementById("filter-bank-account");

    const toggleBankAccountFilter = () => {
        if (!filterPayment || !filterBankAccount) return;
        const container = filterBankAccount.closest('.mb-0') || filterBankAccount.parentElement;
        if (filterPayment.value === "3") { // 3 = Transferencia
            container?.classList.remove("d-none");
        } else {
            container?.classList.add("d-none");
            filterBankAccount.value = "";
        }
    };

    toggleBankAccountFilter();
    filterPayment?.addEventListener("change", toggleBankAccountFilter);

    const filterTable = () => {
        const type = document.getElementById("filter-type")?.value;
        const payment = document.getElementById("filter-payment")?.value;
        const bankAccount = document.getElementById("filter-bank-account")?.value;
        const invoice = document.getElementById("filter-invoice")?.checked;
        const month = document.getElementById("filter-month")?.value;

        console.log("Applying filter:", { type, payment, bankAccount, invoice, month });

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

                const paymentsJson = api.row(dataIndex).node()?.getAttribute("data-payments_detailed");
                if (paymentsJson) {
                    const payments = JSON.parse(paymentsJson);
                    if (payment === "3" && bankAccount) {
                        const hasMatchingBankAccount = payments.some(p => 
                            p.type.toString() === "3" && 
                            p.payment_method_id && 
                            p.payment_method_id.toString() === bankAccount
                        );
                        if (!hasMatchingBankAccount) return false;
                    }
                }
            }
            return true;
        });
        api.draw();
        DataTable.ext.search.pop();
    };

    const ids = ["filter-type", "filter-payment", "filter-bank-account", "filter-invoice"];
    setupCommonUI(filterTable, ids);
    ids.forEach((id) =>
        document.getElementById(id)?.addEventListener("change", filterTable),
    );
}

export function setupExpenseFilters(api) {
    const filterPayment = document.getElementById("filter-payment");
    const filterBankAccount = document.getElementById("filter-bank-account");

    const toggleBankAccountFilter = () => {
        if (!filterPayment || !filterBankAccount) return;
        const container = filterBankAccount.closest('.mb-0') || filterBankAccount.parentElement;
        if (filterPayment.value === "3") { // 3 = Transferencia
            container?.classList.remove("d-none");
        } else {
            container?.classList.add("d-none");
            filterBankAccount.value = "";
        }
    };

    toggleBankAccountFilter();
    filterPayment?.addEventListener("change", toggleBankAccountFilter);

    const filterTable = () => {
        const payment = document.getElementById("filter-payment")?.value;
        const bankAccount = document.getElementById("filter-bank-account")?.value;
        const month = document.getElementById("filter-month")?.value;
        const branch = document.getElementById("filter-branch")?.value;

        DataTable.ext.search.push((settings, data, dataIndex) => {
            if (!settings.nTable.classList.contains("datatable-sm-expenses")) {
                return true;
            }

            const row = api.row(dataIndex).node();
            if (!row) return true;

            const ds = row.dataset;

            // --- filtro por forma de pago ---
            if (payment && ds.payment_type_raw !== payment) {
                return false;
            }

            // --- filtro por cuenta destino ---
            if (payment === "3" && bankAccount && ds.bank_account_id !== bankAccount) {
                return false;
            }

            // --- filtro por fecha (diario/mensual) ---
            if (month && ds.date) {
                // ds.date es DD/MM/YYYY. Lo convertimos a YYYY-MM-DD para comparar usando startsWith
                const [d, m, y] = ds.date.split("/");
                const rowDate = `${y}-${m}-${d}`;
                if (!rowDate.startsWith(month)) return false;
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

    const ids = ["filter-payment", "filter-bank-account", "filter-branch"];
    setupCommonUI(filterTable, ids);

    ids.forEach((id) =>
        document.getElementById(id)?.addEventListener("change", filterTable),
    );
}

export function updateSalesFooter(api) {
    // Obtenemos el valor del filtro de pago actual
    const selectedPaymentFilter =
        document.getElementById("filter-payment")?.value;
    const selectedBankAccountFilter =
        document.getElementById("filter-bank-account")?.value;

    updateGenericFooter(api, (row, totals) => {
        const paymentsJson = row.getAttribute("data-payments_detailed");

        if (paymentsJson) {
            const payments = JSON.parse(paymentsJson);

            payments.forEach((p) => {
                // Si no hay filtro, sumamos todo.
                // Si hay filtro, solo sumamos si el tipo de pago coincide.
                const typeMatches = !selectedPaymentFilter || p.type.toString() === selectedPaymentFilter;
                
                // Si es transferencia y hay filtro de cuenta, validar que coincida
                let bankAccountMatches = true;
                if (selectedPaymentFilter === "3" && selectedBankAccountFilter) {
                    bankAccountMatches = p.payment_method_id && p.payment_method_id.toString() === selectedBankAccountFilter;
                }

                if (typeMatches && bankAccountMatches) {
                    if (p.currency === 1) {
                        // ARS
                        totals.ars += p.amount;
                    } else if (p.currency === 2) {
                        // USD
                        totals.usd += p.amount;
                    }
                }
            });
        } else {
            // Fallback por si acaso no existe el atributo nuevo
            totals.ars += Number(row.getAttribute("data-total_ars") || 0);
            totals.usd += Number(row.getAttribute("data-total_usd") || 0);
        }
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

export function setupOrderFilters(api) {
    const filterTable = () => {
        const status = document.getElementById("filter-status")?.value;
        const source = document.getElementById("filter-source")?.value;
        const month = document.getElementById("filter-month")?.value;

        DataTable.ext.search.push((settings, data, dataIndex) => {
            if (!settings.nTable.classList.contains("datatable-sm-orders")) {
                return true;
            }

            const row = api.row(dataIndex).node();
            if (!row) return true;

            const ds = row.dataset;

            // Filtro por Estado
            if (status && ds.status_raw !== status) return false;

            // Filtro por Origen
            if (source && ds.source_raw !== source) return false;

            // Filtro por Mes (asume formato YYYY-MM en el filtro y YYYY-MM-DD en data-created_at)
            if (month && ds.created_at && !ds.created_at.startsWith(month)) {
                return false;
            }

            return true;
        });

        api.draw();
        DataTable.ext.search.pop();
    };

    const ids = ["filter-status", "filter-source"];
    setupCommonUI(filterTable, ids);

    ids.forEach((id) =>
        document.getElementById(id)?.addEventListener("change", filterTable),
    );
}

export function updateOrderFooter(api) {
    updateGenericFooter(api, (row, totals) => {
        const totalsJson = row.getAttribute("data-totals_detailed");

        if (totalsJson) {
            // Si pasas el array $casts de totales como JSON
            const t = JSON.parse(totalsJson);
            // Asume claves 1 para ARS y 2 para USD según CurrencyType Enum
            totals.ars += Number(t["1"] || 0);
            totals.usd += Number(t["2"] || 0);
        } else {
            // Fallback usando atributos individuales
            totals.ars += Number(row.getAttribute("data-total_ars") || 0);
            totals.usd += Number(row.getAttribute("data-total_usd") || 0);
        }
    });
}
