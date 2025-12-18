// resources/js/modules/products/edit.js
import { MODAL_CONFIG } from "../../config/products.js"; // Retrocede 2 niveles
import { ModalHandler } from "./ModalHandler.js";
import { ImagePreview } from "./ImagePreview.js";
import { ImageRemovalHandler } from "./ImageRemovalHandler.js";
import { ProductEditManager } from "./ProductEditManager.js";

document.addEventListener("DOMContentLoaded", () => {
    const manager = new ProductEditManager(
        new ModalHandler(MODAL_CONFIG),
        new ImagePreview(),
        new ImageRemovalHandler()
    );

    manager.init();
});
