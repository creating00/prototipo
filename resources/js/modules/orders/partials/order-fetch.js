// resources/js/modules/orders/partials/order-fetch.js
export async function fetchProduct(code, branchId) {
    if (!branchId) {
        throw new Error("branchId es requerido y no fue proporcionado");
    }

    const url = `/api/inventory/by-code/${code}?branch_id=${branchId}`;

    const response = await fetch(url);

    if (!response.ok) {
        if (response.status === 404) {
            throw new Error(
                "Producto no encontrado en la sucursal seleccionada"
            );
        }
        throw new Error("Error al buscar el producto");
    }

    return await response.json();
}
