export class CreateModalManager {
    constructor() {
        this.init();
    }

    init() {
        // Escuchar clicks en botones de submit del modal
        document.addEventListener("click", (e) => {
            const submitBtn = e.target.closest("[data-create-modal-submit]");
            if (submitBtn) {
                e.preventDefault();
                this.handleSubmitWithHelper(submitBtn);
            }
        });
    }

    async handleSubmitWithHelper(button) {
        // Prevenir múltiples clicks
        if (button.disabled) return;

        // Deshabilitar botón
        button.disabled = true;
        const originalText = button.innerHTML;
        button.innerHTML =
            '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        try {
            const form = button.closest(".modal").querySelector("form");
            const formData = new FormData(form);

            const route = button.getAttribute("data-route");
            const selectId = button.getAttribute("data-select-id");
            const fieldName = button.getAttribute("data-field-name") || "name";
            const csrfToken = document.querySelector(
                'meta[name="csrf-token"]'
            ).content;

            const response = await fetch(route, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: formData,
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(this.formatError(error));
            }

            const data = await response.json();

            // USAR ChoicesHelper DIRECTAMENTE
            if (selectId && window.ChoicesHelper) {
                const helper = new ChoicesHelper(selectId);
                const success = helper.addOption(
                    data.id,
                    data[fieldName],
                    true
                );

                if (!success) {
                    throw new Error("No se pudo actualizar el selector");
                }
            }

            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(button.closest(".modal"));
            if (modal) modal.hide();

            // Resetear formulario
            form.reset();

            // Mostrar éxito
            this.showSuccess("Guardado exitosamente");
        } catch (error) {
            console.error("Error:", error);
            this.showError("Error: " + error.message);
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    formatError(errorData) {
        if (errorData.errors) {
            return Object.values(errorData.errors).flat().join(", ");
        }
        return errorData.message || "Error desconocido";
    }

    showSuccess(message) {
        if (window.Swal) {
            Swal.fire({
                icon: "success",
                title: message,
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
            });
        } else {
            alert(message);
        }
    }

    showError(message) {
        if (window.Swal) {
            Swal.fire({
                icon: "error",
                title: message,
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 5000,
            });
        } else {
            alert(message);
        }
    }
}
