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
 * Obtiene el ID de sucursal que debe usarse para filtrar stock.
 * Prioridad:
 * 1. branch_recipient_id (si existe)
 * 2. current_branch_id (ventas)
 * 3. branch_id (origen)
 */
export function getCurrentBranchId() {
    // 1. Sucursal destinataria (traspasos / órdenes)
    const recipient = document.querySelector(
        'select[name="branch_recipient_id"]'
    );
    if (recipient?.value) return recipient.value;

    // 2. Input explícito (ventas)
    const branchIdInput = document.getElementById("current_branch_id");
    if (branchIdInput?.value) return branchIdInput.value;

    // 3. Sucursal origen
    const sender = document.querySelector('select[name="branch_id"]');
    if (sender?.value) return sender.value;

    return null;
}
