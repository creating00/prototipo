// resources/js/modules/products/create.js
import { MODAL_CONFIG } from "../../config/products.js";
import { ModalHandler } from "./ModalHandler.js";
import { ImagePreview } from "./ImagePreview.js";

document.addEventListener("DOMContentLoaded", function () {
    const modalHandler = new ModalHandler(MODAL_CONFIG);
    const imagePreview = new ImagePreview();

    modalHandler.init();
    imagePreview.init();

    window.useThisImage = () => imagePreview.useThisImage();

    // Inicializaciones específicas de create
    initCreateSpecific();
});

function initCreateSpecific() {
    //
}
