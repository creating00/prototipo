import orderModal from "./order-modal";

export default {
    init() {
        // Esperar un momento para que el DOM esté completamente cargado
        setTimeout(() => {
            this.setupBranchFilter();
        }, 100);
    },

    setupBranchFilter() {
        // console.log("Setting up branch filter listeners");

        // Detectar qué tipo de formulario estamos usando
        const hiddenBranchInput = document.querySelector(
            'input[name="branch_id"]'
        );
        const branchRecipientSelect = document.querySelector(
            'select[name="branch_recipient_id"]'
        );
        const branchSelect = document.querySelector('select[name="branch_id"]');

        // Formulario de sucursal: existe hiddenBranchInput y branchRecipientSelect
        if (hiddenBranchInput && branchRecipientSelect) {
            // console.log("Formulario de sucursal detectado");
            // console.log("Hidden branch_id value:", hiddenBranchInput.value);
            // console.log(
            //     "branch_recipient_id current value:",
            //     branchRecipientSelect.value
            // );

            // Agregamos listener pero con lógica diferente:
            branchRecipientSelect.addEventListener("change", () => {
                console.log(
                    "branch_recipient_id changed to:",
                    branchRecipientSelect.value
                );
                console.log(
                    "Products still from branch_id (hidden):",
                    hiddenBranchInput.value
                );
                // Aunque los productos no cambian de sucursal, recargamos para datos frescos
                orderModal.reloadTable();
            });
        }
        // Formulario de cliente: existe branchSelect (select)
        else if (branchSelect) {
            // console.log("Formulario de cliente detectado");
            // console.log("branch_id select current value:", branchSelect.value);

            branchSelect.addEventListener("change", () => {
                //console.log("branch_id changed to:", branchSelect.value);
                orderModal.reloadTable();
            });

            // Si hay un valor seleccionado, recargar la tabla inicialmente
            if (branchSelect.value) {
                // console.log("Initial branch_id value found, reloading table");
                // Esperar un poco más para asegurar que todo esté listo
                setTimeout(() => {
                    orderModal.reloadTable();
                }, 500);
            }
        }
        // Si no se encontró ningún control de sucursal, mostrar advertencia
        else {
            console.warn("No branch controls found on this page");
        }
    },
};
