import { Toast } from "bootstrap";

export default class ToastManager {
    constructor() {
        this.init();
    }

    init() {
        this.initializeTriggers();
    }

    initializeTriggers() {
        const toastTriggerList = document.querySelectorAll(
            '[data-bs-toggle="toast"]'
        );

        toastTriggerList.forEach((btn) => {
            btn.addEventListener("click", (event) => {
                event.preventDefault();
                const target = btn.getAttribute("data-bs-target");
                const toastEle = document.getElementById(target);

                if (toastEle) {
                    const toastBootstrap = Toast.getOrCreateInstance(toastEle);
                    toastBootstrap.show();
                }
            });
        });
    }
}
