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
