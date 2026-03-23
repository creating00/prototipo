export async function deleteItem(url, itemName = "este registro") {
    const result = await Swal.fire({
        title: "¿Estás seguro?",
        text: `Vas a eliminar ${itemName}. Esta acción no se puede deshacer.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        reverseButtons: false,
        customClass: {
            confirmButton: "btn btn-danger", // Rojo de Bootstrap
            cancelButton: "btn btn-secondary", // Azul de Bootstrap (en lugar de secondary)
            actions: "gap-3",
        },
        buttonsStyling: false,
    });

    if (result.isConfirmed) {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = url;

        const csrfToken = document.createElement("input");
        csrfToken.type = "hidden";
        csrfToken.name = "_token";
        csrfToken.value =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";

        const methodField = document.createElement("input");
        methodField.type = "hidden";
        methodField.name = "_method";
        methodField.value = "DELETE";

        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}

/**
 * Procesa la eliminación masiva de registros
 * @param {string} url - Endpoint de la API
 * @param {Array} ids - Array de IDs a eliminar
 * @param {Object} manager - Instancia de DataTableManager
 * @param {string} entityName - Nombre de la entidad para el mensaje
 */
export async function deleteBulkItems(
    url,
    ids,
    manager,
    entityName = "registros",
) {
    let successConfirmed = false; // Bandera para silenciar el catch

    try {
        const result = await Swal.fire({
            title: "¿Estás seguro?",
            text: `Se eliminarán ${ids.length} ${entityName} seleccionados.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Sí, eliminar",
            cancelButtonText: "Cancelar",
        });

        if (!result.isConfirmed) return;

        Swal.fire({
            title: "Procesando...",
            didOpen: () => Swal.showLoading(),
            allowOutsideClick: false,
        });

        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({ ids, _method: "DELETE" }),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();

        if (data.success) {
            successConfirmed = true; // A partir de aquí, ignoramos cualquier error de JS

            await Swal.fire("Eliminados", data.message, "success");

            if (manager?.reload) {
                manager.reload();
            }
        } else {
            Swal.fire("Error", data.message || "Error desconocido", "error");
        }
    } catch (error) {
        // Si ya confirmamos éxito o el usuario navegó fuera (Abort), silencio total.
        if (successConfirmed || error.name === "AbortError") return;

        console.error("Bulk delete error:", error);
        Swal.fire("Error", `Detalle: ${error.message}`, "error");
    }
}
