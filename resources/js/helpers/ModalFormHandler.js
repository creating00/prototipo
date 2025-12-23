// resources/js/helpers/ModalFormHandler.js

export class ModalFormHandler {
    /**
     * @param {string|HTMLElement} modal - ID o elemento del modal
     * @param {string} formSelector - Selector del form dentro del modal
     * @param {function|null} onSuccess - Callback cuando el form se guarda correctamente
     */
    constructor(modal, formSelector, onSuccess = null) {
        this.modal = typeof modal === "string" ? document.getElementById(modal) : modal;
        this.formSelector = formSelector;
        this.onSuccess = onSuccess;
    }

    /**
     * Cargar contenido del form vía AJAX
     * @param {string} url 
     */
    async loadForm(url) {
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error("Error al cargar el formulario");
            const html = await res.text();

            const formContent = this.modal.querySelector(`#${this.formSelector}Content`);
            formContent.innerHTML = html;

            this.form = this.modal.querySelector(`#${this.formSelector}`);
            return this.form;
        } catch (err) {
            console.error(err);
            Swal.fire({
                icon: "error",
                title: err.message,
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 5000,
            });
            throw err;
        }
    }

    /**
     * Configura el formulario para submit AJAX
     * @param {string} submitUrl
     * @param {string} method
     */
    bindSubmit(submitUrl, method = "POST") {
        if (!this.form) return;

        this.form.action = submitUrl;
        this.form.method = "POST"; // siempre POST, Laravel usa _method
        if (!this.form.querySelector('input[name="_method"]')) {
            const hiddenMethod = document.createElement("input");
            hiddenMethod.type = "hidden";
            hiddenMethod.name = "_method";
            hiddenMethod.value = method;
            this.form.appendChild(hiddenMethod);
        }

        this.form.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(this.form);
            const data = Object.fromEntries(formData.entries());
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            try {
                const response = await fetch(submitUrl, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: new URLSearchParams(data),
                });

                const responseData = await response.json();

                if (!response.ok) {
                    const errors = responseData.errors
                        ? Object.values(responseData.errors).flat().join(", ")
                        : responseData.message || "Error desconocido";
                    throw new Error(errors);
                }

                bootstrap.Modal.getInstance(this.modal).hide();

                if (this.onSuccess) this.onSuccess(responseData);

                Swal.fire({
                    icon: "success",
                    title: responseData.message || "Guardado correctamente",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                });
            } catch (err) {
                Swal.fire({
                    icon: "error",
                    title: err.message,
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 5000,
                });
            }
        };
    }

    /**
     * Abrir el modal
     */
    open() {
        const bsModal = new bootstrap.Modal(this.modal);
        bsModal.show();
    }

    /**
     * Cierra el modal
     */
    close() {
        bootstrap.Modal.getInstance(this.modal)?.hide();
    }
}
