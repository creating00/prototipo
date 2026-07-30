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
 * Obtiene el ID de sucursal que debe usarse para filtrar stock y buscar productos.
 * Prioridad:
 * 1. branch_id (sucursal proveedora / origen donde está el stock)
 * 2. current_branch_id (ventas)
 * 3. branch_recipient_id / customer_id (sucursal solicitante)
 */
export function getCurrentBranchId() {
    // 1. Sucursal origen / proveedora (donde están los productos y el stock)
    const sender = document.querySelector('select[name="branch_id"]');
    if (sender?.value) return sender.value;

    // 2. Input explícito (ventas)
    const branchIdInput = document.getElementById("current_branch_id");
    if (branchIdInput?.value) return branchIdInput.value;

    // 3. Sucursal destinataria / solicitante (órdenes / traspasos)
    const recipient = document.querySelector(
        'select[name="branch_recipient_id"]'
    );
    if (recipient?.value) return recipient.value;

    const customerSelect = document.querySelector('select[name="customer_id"]');
    if (customerSelect?.value) return customerSelect.value;

    return null;
}
