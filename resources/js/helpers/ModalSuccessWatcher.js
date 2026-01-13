/**
 * Monitorea un modal dinámico para ejecutar una acción solo si el envío fue exitoso.
 */
export class ModalSuccessWatcher {
    /**
     * @param {string} modalId - El ID del elemento modal.
     * @param {Function} onSuccess - Callback a ejecutar si se detecta éxito.
     * @param {string|null} eventName - Nombre del evento global a escuchar para capturar datos (opcional).
     */
    static watch(modalId, onSuccess, eventName = null) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return;

        let formSubmitted = false;
        let capturedData = null;

        const saveBtn = modalElement.querySelector(
            "[data-dynamic-modal-submit]"
        );

        // Handler para capturar datos si se especificó un evento
        const dataHandler = (e) => {
            capturedData = e.detail?.data || e.detail;
        };

        if (eventName) {
            document.addEventListener(eventName, dataHandler);
        }

        const clickHandler = () => {
            formSubmitted = true;
        };

        saveBtn?.addEventListener("click", clickHandler);

        modalElement.addEventListener(
            "hidden.bs.modal",
            function listener() {
                const form = modalElement.querySelector("form");

                if (formSubmitted && saveBtn && !saveBtn.disabled && form) {
                    // Se pasa capturedData (será null si no hay evento o no se disparó)
                    onSuccess(capturedData);
                }

                // Limpieza total
                saveBtn?.removeEventListener("click", clickHandler);
                if (eventName) {
                    document.removeEventListener(eventName, dataHandler);
                }
                modalElement.removeEventListener("hidden.bs.modal", listener);
                formSubmitted = false;
                capturedData = null;
            },
            { once: true }
        );
    }
}
