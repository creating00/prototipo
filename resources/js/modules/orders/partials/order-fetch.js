import { getRepairCategoryId } from "@/helpers/repair-category";
import { Toast } from "@/config/notifications";

export async function fetchProduct(code, branchId, context = 'order') {
    if (!branchId) {
        // Disparamos la alerta antes de lanzar el error
        Toast.fire({
            icon: 'warning',
            title: 'Configuración requerida',
            text: 'La sucursal es necesaria para buscar productos'
        });
        throw new Error("branchId es requerido y no fue proporcionado");
    }

    const categoryId = getRepairCategoryId();

    const url = new URL(
        `/api/inventory/by-code/${code}`,
        window.location.origin
    );

    url.searchParams.append("branch_id", branchId);
    url.searchParams.append("context", context);

    if (categoryId) {
        url.searchParams.append("category_id", categoryId);
    }

    try {
        const response = await fetch(url.toString());

        if (!response.ok) {
            let errorMessage = "Error al buscar el producto";
            
            if (response.status === 404) {
                errorMessage = "Producto no encontrado en la sucursal seleccionada";
            }

            // Notificamos al usuario mediante el Toast importado
            Toast.fire({
                icon: 'error',
                title: 'No encontrado',
                text: errorMessage
            });

            throw new Error(errorMessage);
        }

        const data = await response.json();

        // Opcional: Notificar éxito al encontrarlo
        Toast.fire({
            icon: 'success',
            title: 'Producto encontrado',
            text: data.name,
            timer: 1500 // Más rápido para no distraer
        });

        return data;

    } catch (error) {
        // Captura errores de red (CORS, sin internet, etc.) que no fueron atrapados por response.ok
        if (error.message === "Failed to fetch") {
            Toast.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        }
        
        // Re-lanzamos el error por si la función que llama a fetchProduct necesita manejarlo
        throw error;
    }
}