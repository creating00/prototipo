// resources/js/modules/clients/create.js
import { initOnlyNumbers } from "../../helpers/OnlyNumbers.js";

document.addEventListener("DOMContentLoaded", function () {
    // Inicializar validación solo números para el campo documento
    // const documentValidator = initOnlyNumbers("#document", {
    //     minLength: 7,
    //     maxLength: 8,
    //     format: "document",
    //     onValid: (cleanValue) => {
    //         console.log("Documento válido:", cleanValue);
    //     },
    //     onInvalid: (cleanValue, message) => {
    //         console.warn("Documento inválido:", cleanValue, "-", message);
    //     },
    // });

    // console.log("Módulo de creación/edición de clientes inicializado");

    // // Exponer las instancias si necesitas acceder a ellas desde otros lugares
    // window.clientFormValidators = {
    //     document: documentValidator,
    // };
});
