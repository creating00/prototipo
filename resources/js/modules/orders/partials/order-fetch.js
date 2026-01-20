import { getRepairCategoryId } from "@/helpers/repair-category";
import { Toast } from "@/config/notifications";

export async function fetchProduct(
    code,
    branchId,
    context = "order",
    isRepair = null,
) {
    if (!branchId) {
        Toast.fire({
            icon: "warning",
            title: "Configuración requerida",
            text: "La sucursal es necesaria para buscar productos",
        });
        throw new Error("branchId es requerido");
    }

    // Si isRepair es null (no viene del evento), intentamos detectarlo del DOM
    if (isRepair === null) {
        const saleTypeEl = document.querySelector('select[name="sale_type"]');
        isRepair = saleTypeEl?.value === "2";
    }

    const categoryId = getRepairCategoryId();
    const url = new URL(
        `/api/inventory/by-code/${code}`,
        window.location.origin,
    );

    url.searchParams.append("branch_id", branchId);
    url.searchParams.append("context", context);
    url.searchParams.append("is_repair", isRepair ? "1" : "0");

    if (categoryId) {
        url.searchParams.append("category_id", categoryId);
    }

    try {
        const response = await fetch(url.toString(), {
            headers: { Accept: "application/json" },
        });

        if (!response.ok) {
            let errorMessage = "Error al buscar el producto";
            if (response.status === 404) {
                errorMessage =
                    "Producto no encontrado en la sucursal seleccionada";
            }
            Toast.fire({
                icon: "error",
                title: "No encontrado",
                text: errorMessage,
            });
            throw new Error(errorMessage);
        }

        const data = await response.json();

        Toast.fire({
            icon: "success",
            title: "Producto encontrado",
            text: data.product.name, // Ajustado según tu respuesta JSON del controlador
            timer: 1500,
        });

        return data;
    } catch (error) {
        if (error.message === "Failed to fetch") {
            Toast.fire({
                icon: "error",
                title: "Error de conexión",
                text: "No se pudo conectar con el servidor",
            });
        }
        throw error;
    }
}
