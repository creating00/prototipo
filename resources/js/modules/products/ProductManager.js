// resources/js/modules/products/ProductManager.js
import providerAutocomplete from "./partials/provider-autocomplete.js";

export class ProductManager {
    constructor(modalHandler, imagePreview, imageRemoval = null) {
        this.modalHandler = modalHandler;
        this.imagePreview = imagePreview;
        this.imageRemoval = imageRemoval;
    }

    init(config = {}) {
        this.modalHandler.init();
        this.imagePreview.init();

        if (this.imageRemoval) {
            this.imageRemoval.init();
        }

        // Global para el botón del modal de imágenes
        window.useThisImage = () => this.imagePreview.useThisImage();

        // Inicializar búsqueda de proveedores
        providerAutocomplete.init();

        // Precarga de proveedores si existen (para Edit)
        if (config.existingProviders && config.existingProviders.length > 0) {
            this.hydrateProviders(config.existingProviders);
        }
    }

    hydrateProviders(providers) {
        providers.forEach((p) => {
            providerAutocomplete.selectProvider({
                id: p.id,
                business_name: p.business_name,
                tax_id: p.tax_id,
                phone: p.phone,
            });
        });
    }
}
