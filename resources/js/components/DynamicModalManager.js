import { ModalSubmitHandler } from "./ModalSubmitHandler.js";

export class DynamicModalManager {
    constructor() {
        this.handler = new ModalSubmitHandler();
        this.init();
    }

    init() {
        document.addEventListener("click", (e) => {
            const btn = e.target.closest(
                "[data-dynamic-modal-submit], [data-create-modal-submit]"
            );
            if (btn) {
                e.preventDefault();
                this.handler.handle(btn);
            }
        });
    }
}
