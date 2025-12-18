// resources/js/modules/products/ProductFormManager.js (opcional)
export class ProductFormManager {
    constructor() {
        this.modalHandler = null;
        this.imagePreview = null;
    }

    initCommon() {
        // Inicializar modales comunes
        if (this.modalHandler) {
            this.modalHandler.init();
        }

        // Inicializar preview de imágenes
        if (this.imagePreview) {
            this.imagePreview.init();
            window.useThisImage = () => this.imagePreview.useThisImage();
        }
    }

    preloadImage(imageUrl) {
        if (!imageUrl) {
            return;
        }

        setTimeout(() => {
            const img = new Image();
            img.src = imageUrl;
        }, 100);
    }
}
