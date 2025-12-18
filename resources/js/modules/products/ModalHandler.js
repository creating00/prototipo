// resources/js/modules/products/ModalHandler.js
export class ModalHandler {
    constructor(buttonModalMap = {}) {
        this.buttonModalMap = buttonModalMap;
    }

    init() {
        Object.entries(this.buttonModalMap).forEach(([buttonId, modalId]) => {
            const button = document.getElementById(buttonId);
            if (button) {
                button.setAttribute("data-bs-toggle", "modal");
                button.setAttribute("data-bs-target", `#${modalId}`);
            }
        });
    }
}
