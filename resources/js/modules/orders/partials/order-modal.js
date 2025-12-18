import orderItems from "./order-items";
import { ModalHandler } from "../../products/ModalHandler";
import { MODAL_CONFIG } from "../../../config/order";
import { TABLE_CONFIGS } from "../../../config/tables";
import { DataTableManager } from "../../../components/DataTableManager";
import { getCurrentBranchId } from "../../../config/datatables";

export default {
    dataTableManager: null,

    init() {
        this.setupModalEvents();
        this.ensureTableInitialized();
    },

    setupModalEvents() {
        const btnOpenModal = document.querySelector("#btn-open-product-modal");

        if (btnOpenModal) {
            btnOpenModal.addEventListener("click", () => {
                this.openProductModal();
            });
        }

        document.addEventListener("click", async (e) => {
            const btn = e.target.closest(".btn-select-product");

            if (!btn) return;

            e.preventDefault();

            // Obtener datos del producto desde los atributos data-
            const productData = {
                code: btn.dataset.code,
                name: btn.dataset.name,
                price: btn.dataset.price,
                stock: btn.dataset.stock,
                id: btn.dataset.id,
            };

            const modalEl = document.querySelector("#modal-product-search");
            const modal = bootstrap.Modal.getInstance(modalEl);

            if (modal) {
                modal.hide();
            }

            // Pasar los datos del producto a orderItems
            await orderItems.addProductByCode(productData.code);
        });

        modalHandler.init();
    },

    openProductModal() {
        const modalEl = document.querySelector("#modal-product-search");

        if (!modalEl) {
            console.error("Modal element not found");
            return;
        }

        // Verificar que haya una sucursal seleccionada antes de abrir el modal
        const currentBranchId = getCurrentBranchId();
        if (!currentBranchId) {
            alert("Por favor, seleccione una sucursal primero");
            return;
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Recargar la tabla con la sucursal actual
        setTimeout(() => {
            this.reloadTable();
        }, 300);
    },

    ensureTableInitialized() {
        const tableElement = document.querySelector(
            TABLE_CONFIGS.PRODUCT_MODAL.selector
        );

        if (tableElement) {
            this.dataTableManager = DataTableManager.getInstance(tableElement);

            if (!this.dataTableManager) {
                // console.log("Product modal DataTable - Initializing");
                this.dataTableManager = new DataTableManager(
                    tableElement,
                    TABLE_CONFIGS.PRODUCT_MODAL.options
                );
            }
        }
    },

    reloadTable() {
        const currentBranchId = getCurrentBranchId();

        if (!currentBranchId) {
            console.warn("No branch selected, skipping table reload");
            return;
        }

        if (this.dataTableManager && this.dataTableManager.dataTable) {
            // console.log(
            //     "Product modal - Reloading table for branch:",
            //     currentBranchId
            // );
            this.dataTableManager.reload();
        } else {
            console.warn("Product modal - DataTableManager not initialized");
            this.ensureTableInitialized();
            if (this.dataTableManager && this.dataTableManager.dataTable) {
                this.dataTableManager.reload();
            }
        }
    },
};

const modalHandler = new ModalHandler(MODAL_CONFIG);
