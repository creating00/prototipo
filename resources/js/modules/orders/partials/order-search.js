import orderModal from "./order-modal";

export default {
    init() {
        this.setupSearch();
    },

    setupSearch() {
        const inputCode = document.querySelector("#product_search_code");

        if (inputCode) {
            inputCode.addEventListener("keydown", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();

                    const code = inputCode.value.trim();
                    if (!code) return;

                    document.dispatchEvent(
                        new CustomEvent("product:searchByCode", {
                            detail: { code },
                        })
                    );

                    inputCode.value = "";
                }
            });
        }

        const searchButton = document.querySelector("#btn-search-product");
        if (searchButton) {
            searchButton.addEventListener("click", () => {
                this.openProductModal();
            });
        }
    },

    openProductModal() {
        if (orderModal.dataTable) {
            orderModal.reloadTable();
        }

        $("#productSearchModal").modal("show");
    },
};
