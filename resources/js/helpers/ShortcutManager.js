export default class ShortcutManager {
    constructor(shortcuts = []) {
        this.shortcuts = shortcuts;
        this.init();
    }

    init() {
        document.addEventListener("keydown", (e) => this.handleKeyDown(e));
    }

    handleKeyDown(e) {
        if (!e || !e.key) return;

        const isInput =
            e.target &&
            (e.target.tagName === "INPUT" ||
             e.target.tagName === "TEXTAREA" ||
             e.target.isContentEditable);

        if (Array.isArray(this.shortcuts)) {
            this.shortcuts.forEach((shortcut) => {
                if (!shortcut || !shortcut.key || typeof shortcut.key !== "string") return;

                const keyMatch = e.key.toLowerCase() === shortcut.key.toLowerCase();
                const ctrlMatch = shortcut.ctrl ? e.ctrlKey : true;
                const shiftMatch = shortcut.shift ? e.shiftKey : true;

                if (keyMatch && ctrlMatch && shiftMatch) {
                    if (isInput && !shortcut.allowInInputs) return;

                    e.preventDefault();
                    if (typeof shortcut.action === "function") {
                        shortcut.action();
                    }
                }
            });
        }

        if (e.key === "Escape") {
            const openModalElement = document.querySelector(".modal.show");
            if (openModalElement) {
                const instance = window.bootstrap?.Modal?.getInstance(openModalElement);
                if (instance) instance.hide();
            }
        }
    }

    // Método estático simple: solo dispara el modal.
    static openModal(id, focusSelector = null) {
        const el = document.getElementById(id);
        if (el) {
            // Guardamos el selector deseado en un atributo de datos temporal
            if (focusSelector) {
                el.dataset.shortcutFocus = focusSelector;
            }

            const instance = bootstrap.Modal.getOrCreateInstance(el);
            instance.show();
        }
    }
}
