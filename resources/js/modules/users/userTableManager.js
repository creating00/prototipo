import { TableManager } from "../../components/TableManager";

// Configuración específica de usuarios
const TABLE_CONFIG = {
    tableId: "test-table",
    rowActions: {
        edit: {
            selector: ".btn-edit",
            handler: (row) => {
                const { id, nombre } = row.dataset;
                console.log("Datos completos de la fila:", row.dataset);
                alert(
                    `EDITAR: ID: ${id}, Nombre: ${nombre}\n\nDatos completos:\n${JSON.stringify(
                        row.dataset,
                        null,
                        2
                    )}`
                );
            },
        },
        delete: {
            selector: ".btn-delete",
            handler: (row) => {
                const { id, nombre } = row.dataset;
                if (
                    confirm(
                        `¿Estás seguro de eliminar a ${nombre} (ID: ${id})?`
                    )
                ) {
                    alert(`Usuario ${nombre} eliminado (simulación)`);
                }
            },
        },
    },
    headerActions: {
        new: {
            selector: ".btn-header-new",
            message:
                "Botón NUEVO clickeado - Aquí iría el formulario de crear usuario",
        },
        deleted: {
            selector: ".btn-header-deleted",
            message:
                "Botón ELIMINADOS clickeado - Mostraría usuarios eliminados",
        },
        print: {
            selector: ".btn-header-print",
            message: "Botón IMPRIMIR clickeado - Generaría reporte PDF/Excel",
        },
    },
};

export function initUserTable() {
    return TableManager.initTable(TABLE_CONFIG);
}

export default {
    init: initUserTable,
    config: TABLE_CONFIG,
};
