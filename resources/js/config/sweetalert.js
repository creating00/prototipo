// Configuración limpia sin dependencias de Bootstrap
const BootstrapSwal = Swal.mixin({
    reverseButtons: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#6e7881",
});

BootstrapSwal.delete = function (itemName = "este registro") {
    return this.fire({
        title: "¿Estás seguro?",
        text: `Vas a eliminar ${itemName}. Esta acción no se puede deshacer.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33",
    });
};

BootstrapSwal.success = function (title = "¡Éxito!", text = "") {
    return this.fire({
        title,
        text,
        icon: "success",
        confirmButtonText: "Aceptar",
    });
};

BootstrapSwal.error = function (title = "Error", text = "") {
    return this.fire({
        title,
        text,
        icon: "error",
        confirmButtonText: "Entendido",
    });
};

BootstrapSwal.confirmReceive = function (orderNumber = "") {
    return this.fire({
        title: "¿Confirmar recepción?",
        html: `
            <div style="text-align: center;">
                <p style="margin-bottom: 10px;">¿Deseas marcar la orden <strong>${orderNumber}</strong> como recibida?</p>
                <p style="color: #6c757d; font-size: 0.875em;">El stock de los productos se incrementará automáticamente.</p>
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Sí, recibir mercadería",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#28a745",
    });
};

BootstrapSwal.confirmReceiveWithObservation = function (orderNumber = "") {
    return this.fire({
        title: "¿Confirmar recepción?",
        text: `¿Deseas marcar la orden #${orderNumber} como recibida?`,
        icon: "question",
        input: "textarea", // Usamos el input nativo de Swal, es más seguro
        inputPlaceholder: "Observaciones (opcional)...",
        showCancelButton: true,
        confirmButtonText: "Confirmar Recepción",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#28a745",
    });
};

export default BootstrapSwal;
