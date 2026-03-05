import { TableManager } from "../../components/TableManager";
import { deleteItem, deleteBulkItems } from "../../utils/deleteHelper";
import { ModalSuccessWatcher } from "../../helpers/ModalSuccessWatcher";
import { UIHelper } from "../../components/UIHelper";
//import { DataTableManager } from "../../components/DataTableManager";

const TABLE_CONFIG = {
    tableId: "products-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row, baseUrl) => {
                const { id } = row.dataset;
                // Ahora es automático: /web/products + / + id + /edit
                window.location.href = `${baseUrl}/${id}/edit`;
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row, baseUrl) => {
                const { id, name } = row.dataset;
                // /web/products + / + id
                deleteItem(`${baseUrl}/${id}`, `el producto "${name}"`);
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            handler: (baseUrl) => {
                window.location.href = `${baseUrl}/create`;
            },
        },
        newProvider: {
            selector: ".btn-header-new-provider",
            handler: () => {
                const modalId = "modalProvider";
                const modalElement = document.getElementById(modalId);

                if (modalElement) {
                    const modal =
                        bootstrap.Modal.getOrCreateInstance(modalElement);

                    // Observamos el éxito del modal para refrescar la tabla
                    ModalSuccessWatcher.watch(modalId, () => {
                        window.location.reload();
                    });

                    modal.show();
                }
            },
        },
        importExcel: {
            selector: ".btn-header-import",
            handler: (baseUrl, event) => {
                UIHelper.handleImport(
                    event.currentTarget,
                    "import-excel-input",
                    `${baseUrl}/import`,
                    "productos",
                );
            },
        },

        importProviders: {
            selector: ".btn-header-import-providers",
            handler: (baseUrl, event) => {
                const btn = event.currentTarget;
                const importUrl = btn.dataset.importUrl;

                UIHelper.handleImport(
                    btn,
                    "import-providers-excel-input",
                    importUrl,
                    "proveedores",
                );
            },
        },

        downloadTemplate: {
            selector: ".btn-download-template",
            handler: (baseUrl, event) => {
                UIHelper.handleDownload(event.currentTarget, event);
            },
        },

        bulkDelete: {
            selector: "#btn-bulk-delete",
            handler: (baseUrl, event) => {
                event.preventDefault();

                const tableElement = document.getElementById(
                    TABLE_CONFIG.tableId,
                );
                const manager = DataTableManager.getInstance(tableElement);
                if (!manager) return;

                const ids = manager.getSelectedIds();
                if (ids.length === 0) return;

                UIHelper.handleBulkDelete(
                    event.currentTarget,
                    `${baseUrl}/bulk-delete`,
                    ids,
                    manager,
                    "productos",
                );
            },
        },
    },
};

export function initProductTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initProductTable,
    config: TABLE_CONFIG,
};
