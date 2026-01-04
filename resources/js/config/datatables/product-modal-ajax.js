import { getRepairCategoryId } from "@/helpers/repair-category";

/**
 * Carga productos vía AJAX filtrando por sucursal y tipo de reparación.
 * @param {Object} data - Parámetros de DataTables.
 * @param {Function} callback - Retorno de datos a la tabla.
 */
export function productModalAjax(data, callback, settings) {
    const branchId = getCurrentBranchId();
    const categoryId = getRepairCategoryId();

    if (!branchId) {
        callback({ data: [] });
        return null;
    }

    const url = new URL("/api/inventory/list", window.location.origin);
    url.searchParams.append("branch_id", branchId);

    if (categoryId) {
        url.searchParams.append("category_id", categoryId);
    }

    const controller = new AbortController();

    fetch(url.toString(), { signal: controller.signal })
        .then((res) => (res.ok ? res.json() : Promise.reject(res)))
        .then((json) => callback({ data: Array.isArray(json) ? json : [] }))
        .catch((err) => {
            if (err.name !== "AbortError") {
                console.error("Error productos:", err);
            }
            callback({ data: [] });
        });

    return { abort: () => controller.abort() };
}

/**
 * Obtiene el ID de sucursal que provee el stock (Origen).
 * @returns {string|null}
 */
export function getCurrentBranchId() {
    // 1. Buscamos el input explícito de sucursal de origen (Ventas)
    const branchIdInput = document.getElementById("current_branch_id");
    if (branchIdInput?.value) return branchIdInput.value;

    // 2. Fallback para Órdenes/Traspasos si fuera necesario
    const sender = document.querySelector('select[name="branch_id"]');
    const recipient = document.querySelector(
        'select[name="branch_recipient_id"]'
    );

    // En Ventas, siempre queremos el origen para ver stock
    return sender?.value || recipient?.value || null;
}
