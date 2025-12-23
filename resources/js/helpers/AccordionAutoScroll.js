// resources/js/helpers/AccordionAutoScroll.js
export default class AccordionAutoScroll {
    /**
     * Constructor
     * @param {string|HTMLElement} accordion - ID del acordeón o elemento DOM
     * @param {number} offset - Offset en px para navbar u otros elementos
     */
    constructor(accordion, offset = 100) {
        if (typeof accordion === "string") {
            this.accordion = document.getElementById(accordion);
        } else {
            this.accordion = accordion;
        }

        if (!this.accordion) return;

        this.offset = offset;
        this.init();
    }

    // Scroll al header
    scrollToHeader(header) {
        const top =
            header.getBoundingClientRect().top +
            window.pageYOffset -
            this.offset;
        window.scrollTo({ top, behavior: "smooth" });
    }

    // Inicializar observador
    init() {
        const collapses = this.accordion.querySelectorAll(
            ".accordion-collapse"
        );

        // Observador de cambios en clases
        const observer = new MutationObserver((mutationsList) => {
            mutationsList.forEach((mutation) => {
                if (
                    mutation.type === "attributes" &&
                    mutation.attributeName === "class"
                ) {
                    const target = mutation.target;
                    if (target.classList.contains("show")) {
                        const header = target.previousElementSibling;
                        if (header) this.scrollToHeader(header);
                    }
                }
            });
        });

        collapses.forEach((collapseEl) =>
            observer.observe(collapseEl, { attributes: true })
        );

        // Scroll inicial si alguna sección ya está abierta
        const openSection = this.accordion.querySelector(
            ".accordion-collapse.show"
        );
        if (openSection) {
            const header = openSection.previousElementSibling;
            if (header) this.scrollToHeader(header);
        }
    }
}
