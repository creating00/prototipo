// resources/js/modules/products/ProductEditManager.js
export class ProductEditManager {
    constructor(modalHandler, imagePreview, imageRemovalHandler) {
        this.modalHandler = modalHandler;
        this.imagePreview = imagePreview;
        this.imageRemovalHandler = imageRemovalHandler;
        this.deleteForm = null;
    }

    init() {
        // Inicializar componentes
        this.modalHandler?.init();
        this.imagePreview?.init();
        this.imageRemovalHandler?.init();

        // Hacer disponible globalmente
        window.useThisImage = () => this.imagePreview?.useThisImage();

        // Inicializar funcionalidades específicas
        this.initEditSpecific();
    }

    initEditSpecific() {
        this.preloadCurrentImage();
        this.setupDeleteConfirmation();
    }

    preloadCurrentImage() {
        const currentImageUrl = document.getElementById("image_url")?.value;
        if (currentImageUrl) {
            // Pre-cargar imagen para cache
            setTimeout(() => {
                const img = new Image();
                img.src = currentImageUrl;
            }, 100);
        }
    }

    setupDeleteConfirmation() {
        this.deleteForm = document.querySelector('form[action*="destroy"]');
        if (!this.deleteForm) {
            return;
        }

        this.deleteForm.addEventListener("submit", (e) => {
            if (
                !confirm(
                    "¿Está seguro de eliminar este producto? Esta acción no se puede deshacer."
                )
            ) {
                e.preventDefault();
            }
        });
    }

    destroy() {
        this.imageRemovalHandler?.destroy();
        this.deleteForm?.removeEventListener(
            "submit",
            this.handleDeleteConfirmation
        );
    }
}
