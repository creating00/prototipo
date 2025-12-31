export default class ViewManager {
    /**
     * Gestiona el scroll inicial priorizando alertas y devolviendo el foco
     * @param {string} mainElementSelector - Selector de la card principal
     * @param {string} focusInputSelector - ID o selector del input a enfocar
     */
    static initSmartScroll(mainElementSelector, focusInputSelector) {
        const alertElement = document.querySelector(".alert");
        const mainCard = document.querySelector(mainElementSelector);
        const focusInput = document.querySelector(focusInputSelector);

        const scrollToMain = () => {
            if (mainCard) {
                mainCard.scrollIntoView({ behavior: "smooth", block: "start" });
                if (focusInput) focusInput.focus();
            }
        };

        setTimeout(() => {
            if (alertElement) {
                // 1. Ir a la alerta
                alertElement.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });

                // 2. Escuchar cierre de Bootstrap
                alertElement.addEventListener("closed.bs.alert", scrollToMain);

                // 3. Escuchar eliminación manual del DOM
                const observer = new MutationObserver(() => {
                    if (!document.body.contains(alertElement)) {
                        scrollToMain();
                        observer.disconnect();
                    }
                });
                observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                });
            } else {
                scrollToMain();
            }

            if (focusInput) focusInput.focus();
        }, 400);
    }
}
