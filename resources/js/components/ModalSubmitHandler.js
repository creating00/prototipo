import { HttpClient } from "./HttpClient.js";
import { UIHelper } from "./UIHelper.js";
import { SelectUpdater } from "./SelectUpdater.js";

export class ModalSubmitHandler {
    constructor() {
        this.selectUpdater = new SelectUpdater();
    }

    async handle(button) {
        UIHelper.disableButton(button);

        try {
            const modal = button.closest(".modal");
            const form = modal.querySelector("form");
            const formData = new FormData(form);

            const route = button.dataset.route;
            const selectId = button.dataset.selectId;
            const fieldName = button.dataset.fieldName || "name";
            const refreshOnSave = button.dataset.refreshOnSave === "true";
            const refreshUrl = button.dataset.refreshUrl;
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
            form.reset();
            UIHelper.success("Guardado exitosamente");
        } catch (error) {
            UIHelper.error(error.message);
        } finally {
            UIHelper.enableButton(button);
        }
    }
}
