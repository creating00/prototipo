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

        document.addEventListener("keydown", (e) => {
            if (e.key === "Enter" && e.target.tagName !== "TEXTAREA") {
                const modal = e.target.closest(".modal.show");
                if (modal) {
                    e.preventDefault();
                    const btn = modal.querySelector(
                        "[data-dynamic-modal-submit]"
                    );
                    if (btn && !btn.disabled) {
                        this.handler.handle(btn);
                    }
                }
            }
        });

        document.addEventListener("shown.bs.modal", (e) => {
            const modal = e.target;
            const trigger = e.relatedTarget;

            setTimeout(() => {
                // ORDEN DE PRIORIDAD:
                // 1. Si el modal tiene un selector guardado por el ShortcutManager
                // 2. Si el botón físico tiene data-focus-target
                // 3. El buscador de DataTables o el primer input visible
                const shortcutTarget = modal.dataset.shortcutFocus;
                const buttonTarget = trigger?.dataset?.focusTarget;

                const finalSelector = shortcutTarget || buttonTarget;

                const input = finalSelector
                    ? modal.querySelector(finalSelector)
                    : modal.querySelector(
                          'input[type="search"], input:not([type="hidden"]):not([disabled]), textarea:not([disabled])'
                      );

                if (input) {
                    input.focus();
                    if (input.tagName === "INPUT" && input.type !== "number")
                        input.select();
                }

                // Limpiamos el atributo para que no afecte a la próxima vez que se abra
                delete modal.dataset.shortcutFocus;
            }, 150);
        });
    }
}
