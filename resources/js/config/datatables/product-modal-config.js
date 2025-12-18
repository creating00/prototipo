import { productModalAjax } from "./product-modal-ajax.js";

export const PRODUCT_MODAL_CONFIG = {
    selector: ".datatable-product-modal",
    options: {
        pageLength: 5,
        ordering: false,
        ajax: productModalAjax,
        columns: [
            {
                data: "code",
                title: "Código",
                className: "text-center",
            },
            {
                data: "name",
                title: "Nombre",
                className: "text-left",
            },
            {
                data: "stock",
                title: "Stock",
                className: "text-center",
                render: function (data, type, row) {
                    if (type === "display") {
                        return `<span class="badge bg-info">${data}</span>`;
                    }
                    return data;
                },
            },
            {
                data: "price",
                title: "Precio",
                className: "text-end",
                render: function (data, type, row) {
                    if (type === "display") {
                        return `$${parseFloat(data).toFixed(2)}`;
                    }
                    return data;
                },
            },
            {
                data: null,
                title: "Acción",
                className: "text-center",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <button 
                            class="btn btn-success btn-sm btn-select-product"
                            data-code="${row.code}"
                            data-name="${row.name}"
                            data-price="${row.price}"
                            data-stock="${row.stock}"
                            data-id="${row.id}">
                            <i class="fas fa-plus"></i> Seleccionar
                        </button>
                    `;
                },
            },
        ],
        // Configuraciones adicionales de DataTables
        processing: true,
        serverSide: false,
        deferRender: true,
        responsive: true,
        language: {
            url: "/vendor/datatables/lang/es-ES.json",
        },
        initComplete: function (settings, json) {
            //console.log("Modal de productos - DataTable inicializado");
        },
        drawCallback: function (settings) {
            //console.log("Modal de productos - Tabla redibujada");
        },
    },
};
