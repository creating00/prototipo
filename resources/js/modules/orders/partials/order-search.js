import orderItems from "./order-items";
import orderModal from "./order-modal";

export default {
    init() {
        this.setupSearch();
    },

    setupSearch() {
        const inputCode = document.querySelector("#product_search_code");

        if (inputCode) {
            inputCode.addEventListener("keydown", async (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    const code = inputCode.value.trim();
                    if (code !== "") {
                        await orderItems.addProductByCode(code);
                        inputCode.value = ""; // Limpiar el campo después de agregar
                    }
                }
            });
        }

        // Configurar el botón de búsqueda manual
        const searchButton = document.querySelector("#btn-search-product");
        if (searchButton) {
            searchButton.addEventListener("click", () => {
                this.openProductModal();
            });
        }
    },

    openProductModal() {
        // Asegurarse de que la tabla esté actualizada
        if (orderModal.dataTable) {
            orderModal.reloadTable();
        }

        // Mostrar el modal
        $("#productSearchModal").modal("show");
    },
};
