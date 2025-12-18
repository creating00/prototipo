import { PRODUCT_MODAL_CONFIG } from "./datatables/product-modal-config.js";

export const TABLE_CONFIGS = {
    MAIN: {
        selector: ".datatable-main",
        options: {
            pageLength: 10,
            ordering: true,
        },
    },

    SMALL: {
        selector: ".datatable-sm",
        options: {
            pageLength: 5,
            ordering: false,
        },
    },

    PRODUCT_MODAL: PRODUCT_MODAL_CONFIG,
};
