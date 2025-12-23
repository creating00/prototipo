import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";
import { ModalFormHandler } from "../../helpers/ModalFormHandler";
import { ModalSuccessWatcher } from "../../helpers/ModalSuccessWatcher";

const TABLE_CONFIG = {
    tableId: "provider-products-table",

    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: async (row, baseUrl) => {
                const { id } = row.dataset;

                // Crear modal handler con callback onSuccess
                const modalHandler = new ModalFormHandler(
                    "editProviderProductModal",
                    "editProviderProductForm",
                    (data) => {
                        window.location.reload();
                    }
                );

                // Cargar el form vía AJAX
                await modalHandler.loadForm(
                    `${baseUrl}/provider-products/${id}/edit`
                );

                // Configurar submit AJAX
                modalHandler.bindSubmit(
                    `${baseUrl}/provider-products/${id}`,
                    "PUT"
                );

                // Abrir el modal
                modalHandler.open();
            },
        },
        price: {
            selector: ".btn-price",
            handler: async (row, baseUrl) => {
                const { id } = row.dataset;

                const modalHandler = new ModalFormHandler(
                    "providerProductPricesModal",
                    "providerProductPriceForm",
                    (data) => {
                        location.reload(); // para simplificar
                    }
                );

                await modalHandler.loadForm(
                    `${baseUrl}/provider-products/${id}/prices`
                );

                modalHandler.bindSubmit(
                    `${baseUrl}/provider-products/${id}/prices`,
                    "POST"
                );

                modalHandler.open();
            },
        },
    },

    headerActions: {
        attachProduct: {
            selector: ".btn-header-attach-product",
            handler: (baseUrl) => {
                const modalId = "attachProductModal";
                const modalElement = document.getElementById(modalId);

                if (modalElement) {
                    const modal =
                        bootstrap.Modal.getOrCreateInstance(modalElement);

                    // Usamos nuestro nuevo helper
                    ModalSuccessWatcher.watch(modalId, () => {
                        window.location.reload();
                    });

                    modal.show();
                }
            },
        },
    },
};

export function initProviderProductTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initProviderProductTable,
    config: TABLE_CONFIG,
};
