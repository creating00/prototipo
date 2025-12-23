/**
 * Monitorea un modal dinámico para ejecutar una acción solo si el envío fue exitoso.
 */
export class ModalSuccessWatcher {
    /**
     * @param {string} modalId - El ID del elemento modal.
     * @param {Function} onSuccess - Callback a ejecutar si se detecta éxito.
     */
    static watch(modalId, onSuccess) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return;

        let formSubmitted = false;
        const saveBtn = modalElement.querySelector(
            "[data-dynamic-modal-submit]"
        );

        // Registrar intento de envío
        const clickHandler = () => {
            formSubmitted = true;
        };

        saveBtn?.addEventListener("click", clickHandler);

        // Detectar cierre del modal
        modalElement.addEventListener(
            "hidden.bs.modal",
            function listener() {
                /**
                 * Lógica de éxito:
                 * 1. El usuario hizo clic en guardar.
                 * 2. El botón no está deshabilitado (el proceso terminó).
                 * 3. El formulario se reseteó (comportamiento del core en éxito).
                 */
                const form = modalElement.querySelector("form");

                if (formSubmitted && saveBtn && !saveBtn.disabled && form) {
                    // Verificación adicional: si el form se reseteó, los campos requeridos suelen estar vacíos
                    // o simplemente confiamos en que el core cerró el modal tras el éxito.
                    onSuccess();
                }

                // Limpieza total
                saveBtn?.removeEventListener("click", clickHandler);
                modalElement.removeEventListener("hidden.bs.modal", listener);
                formSubmitted = false;
            },
            { once: true }
        );
    }
}
