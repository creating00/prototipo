export default class HeightSync {
    constructor(sourceSelector, targetSelector) {
        this.source = document.querySelector(sourceSelector);
        this.target = document.querySelector(targetSelector);
    }

    init() {
        if (!this.source || !this.target) return;

        const applyHeight = () => {
            const height = window.getComputedStyle(this.source).height;
            this.target.style.height = height;
        };

        // Inicial
        applyHeight();

        // Observa cambios en el source
        const observer = new ResizeObserver(applyHeight);
        observer.observe(this.source);

        // También escucha resize global (por si cambia zoom/viewport)
        window.addEventListener("resize", applyHeight);
    }
}
