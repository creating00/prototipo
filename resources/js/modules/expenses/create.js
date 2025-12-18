// resources/js/modules/products/create.js
import { MODAL_CONFIG } from "../../config/expenses.js";
import { ModalHandler } from "../products/ModalHandler";
import HeightSync from "../../helpers/HeightSync";

document.addEventListener("DOMContentLoaded", function () {
    const modalHandler = new ModalHandler(MODAL_CONFIG);

    modalHandler.init();

    // Inicializaciones específicas de create
    initCreateSpecific();
});

function initCreateSpecific() {
    const sync = new HeightSync(
        ".choices__inner",
        ".currency-price-input .form-control"
    );
    sync.init();
}
