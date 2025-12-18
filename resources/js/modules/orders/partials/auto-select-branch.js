import orderModal from "./order-modal";

export default {
    init() {
        setTimeout(() => {
            this.autoSelectFirstBranch();
        }, 100);
    },

    autoSelectFirstBranch() {
        const branchRecipientSelect = document.querySelector(
            'select[name="branch_recipient_id"]'
        );

        if (branchRecipientSelect) {
            console.log("Auto-select - Encontrado select branch_recipient_id");

            if (
                !branchRecipientSelect.value ||
                branchRecipientSelect.value === ""
            ) {
                console.log(
                    "Auto-select - Select vacío, buscando primera opción..."
                );

                for (let option of branchRecipientSelect.options) {
                    if (option.value && option.value !== "") {
                        console.log(
                            `Auto-select - Seleccionando: ${option.value}`
                        );
                        branchRecipientSelect.value = option.value;

                        // Disparar eventos para que otros componentes reaccionen
                        const changeEvent = new Event("change", {
                            bubbles: true,
                        });
                        branchRecipientSelect.dispatchEvent(changeEvent);

                        // Recargar la tabla después de seleccionar
                        setTimeout(() => {
                            orderModal.reloadTable();
                        }, 200);

                        break;
                    }
                }
            } else {
                console.log(
                    "Auto-select - Ya tiene valor:",
                    branchRecipientSelect.value
                );
            }
        }
    },
};
