import { HttpClient } from "./HttpClient.js";
import { UIHelper } from "./UIHelper.js";
import { SelectUpdater } from "./SelectUpdater.js";

export class ModalSubmitHandler {
    constructor() {
        this.selectUpdater = new SelectUpdater();
    }

    async handle(button) {
        const modal = button.closest(".modal");
        const form = modal.querySelector("form");

        // 1. Validación HTML5
        if (!this.validateForm(form)) return;

        UIHelper.disableButton(button);

        try {
            const formData = new FormData(form);
            const route = button.dataset.route;
            const isLocalSubmit = button.dataset.localSubmit === "true";
            const selectId = button.dataset.selectId;
            const fieldName = button.dataset.fieldName || "name";
            const refreshOnSave = button.dataset.refreshOnSave === "true";
            const refreshUrl = button.dataset.refreshUrl;

            /**
             * ==============================
             * MODAL LOCAL (SIN BACKEND)
             * ==============================
             */
            if (isLocalSubmit || !route) {
                modal.dispatchEvent(
                    new CustomEvent("dynamic-modal:local-submit", {
                        detail: {
                            form,
                            formData,
                            modalId: modal.id,
                        },
                    })
                );

                bootstrap.Modal.getInstance(modal)?.hide();

                // Limpieza básica
                form.classList.remove("was-validated");
                return;
            }

            /**
             * ==============================
             * MODAL NORMAL (CON BACKEND)
             * ==============================
             */
            const csrf = document.querySelector(
                'meta[name="csrf-token"]'
            )?.content;

            const data = await HttpClient.post(route, formData, csrf);

            if (selectId) {
                await this.selectUpdater.update(
                    selectId,
                    data,
                    fieldName,
                    refreshOnSave,
                    refreshUrl
                );
            }

            bootstrap.Modal.getInstance(modal)?.hide();

            // Limpieza completa
            form.reset();
            form.classList.remove("was-validated");

            UIHelper.success("Guardado exitosamente");
        } catch (error) {
            UIHelper.error(error.message);
        } finally {
            UIHelper.enableButton(button);
        }
    }

    /**
     * Valida el formulario usando la API nativa de HTML5
     */
    validateForm(form) {
        if (!form.checkValidity()) {
            form.classList.add("was-validated");

            const firstInvalid = form.querySelector(":invalid");
            if (firstInvalid) firstInvalid.focus();

            return false;
        }
        return true;
    }
}
