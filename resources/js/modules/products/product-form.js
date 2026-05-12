// resources/js/modules/products/product-form.js
import { MODAL_CONFIG } from "../../config/products.js";
import { ModalHandler } from "./ModalHandler.js";
import { ImagePreview } from "./ImagePreview.js";
import { ImageRemovalHandler } from "./ImageRemovalHandler.js";
import { ProductManager } from "./ProductManager.js";

document.addEventListener("DOMContentLoaded", () => {
    const manager = new ProductManager(
        new ModalHandler(MODAL_CONFIG),
        new ImagePreview(),
        // Solo instanciamos el removal si el elemento existe en el DOM
        document.getElementById("removeImage")
            ? new ImageRemovalHandler()
            : null
    );

    // Buscamos datos inyectados desde el Blade
    const existingData = window.ProductFormData || {};
    manager.init(existingData);
});
