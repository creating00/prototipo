import { TableManager } from "../../components/TableManager";
import { deleteItem } from "../../utils/deleteHelper";
import { ModalSuccessWatcher } from "../../helpers/ModalSuccessWatcher";
import { UIHelper } from "../../components/UIHelper";

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
            handler: (baseUrl) => {
                const fileInput = document.getElementById("import-excel-input");
                const btn = document.querySelector(".btn-header-import");

                if (!fileInput) return;

                fileInput.value = "";
                fileInput.onchange = async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;

                    // Validación de extensión
                    const allowed = ["xlsx", "xls", "csv"];
                    const ext = file.name.split(".").pop().toLowerCase();

                    if (!allowed.includes(ext)) {
                        UIHelper.error(
                            `Selecciona un archivo válido: ${allowed.join(", ")}`,
                        );
                        fileInput.value = "";
                        return;
                    }

                    // UI: Estado cargando
                    UIHelper.disableButton(btn, "Subiendo...");

                    // Loading modal
                    Swal.fire({
                        title: "Importando productos...",
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });

                    const formData = new FormData();
                    formData.append("file", file);

                    try {
                        const { data } = await axios.post(
                            "/web/products/import",
                            formData,
                            {
                                headers: {
                                    "Content-Type": "multipart/form-data",
                                },
                            },
                        );

                        UIHelper.success(data.message || "Importación exitosa");

                        // Recarga diferida para que vean el toast
                        setTimeout(() => window.location.reload(), 1500);
                    } catch (error) {
                        console.error(error);
                        const msg =
                            error.response?.data?.error || "Error al importar";
                        Swal.fire("Error", msg, "error");
                    } finally {
                        UIHelper.enableButton(btn);
                    }
                };

                fileInput.click();
            },
        },

        importProviders: {
            selector: ".btn-header-import-providers",
            handler: (baseUrl) => {
                const fileInput = document.getElementById(
                    "import-providers-excel-input",
                );
                const btn = document.querySelector(
                    ".btn-header-import-providers",
                );

                if (!fileInput) return;

                fileInput.value = "";
                fileInput.onchange = async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;

                    const allowed = ["xlsx", "xls", "csv"];
                    const ext = file.name.split(".").pop().toLowerCase();

                    if (!allowed.includes(ext)) {
                        UIHelper.error(
                            `Selecciona un archivo válido: ${allowed.join(", ")}`,
                        );
                        fileInput.value = "";
                        return;
                    }

                    UIHelper.disableButton(btn, "Subiendo...");

                    Swal.fire({
                        title: "Importando proveedores...",
                        text: "Esto puede demorar unos segundos",
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });

                    const formData = new FormData();
                    formData.append("file", file);

                    try {
                        const { data } = await axios.post(
                            "/web/providers/import", // Ajusta esta ruta según tu api.php/web.php
                            formData,
                            {
                                headers: {
                                    "Content-Type": "multipart/form-data",
                                },
                            },
                        );

                        UIHelper.success(
                            data.message ||
                                "Importación de proveedores exitosa",
                        );
                        setTimeout(() => window.location.reload(), 1500);
                    } catch (error) {
                        console.error(error);
                        const msg =
                            error.response?.data?.error ||
                            "Error al importar proveedores";
                        Swal.fire("Error", msg, "error");
                    } finally {
                        UIHelper.enableButton(btn);
                    }
                };

                fileInput.click();
            },
        },

        downloadTemplate: {
            selector: ".btn-download-template",
            handler: async (baseUrl, event) => {
                event.preventDefault(); // Evitamos la descarga directa

                const btn = event.currentTarget;
                const url = btn.href;
                const type = btn.dataset.type;

                UIHelper.disableButton(btn, "Preparando...");

                try {
                    const response = await axios({
                        url: url,
                        method: "GET",
                        responseType: "blob", // Importante para archivos
                    });

                    // Crear un link temporal para disparar la descarga
                    const blob = new Blob([response.data]);
                    const link = document.createElement("a");
                    link.href = window.URL.createObjectURL(blob);
                    link.download = `plantilla_${type}.xlsx`;
                    link.click();

                    UIHelper.success("Descarga iniciada");
                } catch (error) {
                    console.error("Error en descarga:", error);
                    const msg =
                        "La plantilla no está disponible en el servidor.";
                    UIHelper.error(msg);
                } finally {
                    UIHelper.enableButton(btn);
                }
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
