// Configuración global para Bootstrap 5
const BootstrapSwal = Swal.mixin({
    customClass: {
        popup: "border-radius-1 shadow-lg",
        title: "h4 mb-3 fw-bold",
        htmlContainer: "text-muted",
        confirmButton: "btn btn-primary",
        cancelButton: "btn btn-secondary me-2",
        denyButton: "btn btn-warning me-2",
        actions: "gap-2 mt-3",
        input: "form-control",
    },
    buttonsStyling: false,
    reverseButtons: true,
    showClass: {
        popup: "animate__animated animate__fadeInDown",
    },
    hideClass: {
        popup: "animate__animated animate__fadeOutUp",
    },
});

// Métodos helpers específicos
BootstrapSwal.delete = function (itemName = "este registro") {
    return this.fire({
        title: "¿Estás seguro?",
        html: `
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                <p class="mb-0">Vas a eliminar <strong>${itemName}</strong></p>
                <p class="text-muted small mt-1">Esta acción no se puede deshacer</p>
            </div>
        `,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash me-2"></i>Eliminar',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d",
        customClass: {
            confirmButton: "btn btn-danger btn-lg",
            cancelButton: "btn btn-secondary btn-lg me-2",
        },
    });
};

BootstrapSwal.success = function (title = "¡Éxito!", text = "") {
    return this.fire({
        title,
        text,
        icon: "success",
        confirmButtonText: '<i class="fas fa-check me-2"></i>Aceptar',
        customClass: {
            confirmButton: "btn btn-success",
        },
    });
};

BootstrapSwal.error = function (title = "Error", text = "") {
    return this.fire({
        title,
        text,
        icon: "error",
        confirmButtonText: '<i class="fas fa-times me-2"></i>Entendido',
        customClass: {
            confirmButton: "btn btn-danger",
        },
    });
};

BootstrapSwal.confirmReceive = function (orderNumber = "") {
    return this.fire({
        title: "¿Confirmar recepción?",
        html: `
            <div class="text-center">
                <i class="fas fa-truck-loading text-success fa-3x mb-3"></i>
                <p class="mb-0">¿Deseas marcar la orden <strong>${orderNumber}</strong> como recibida?</p>
                <p class="text-muted small mt-1">El stock de los productos se incrementará automáticamente.</p>
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText:
            '<i class="fas fa-check me-2"></i>Sí, recibir mercadería',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        customClass: {
            confirmButton: "btn btn-success btn-lg",
            cancelButton: "btn btn-secondary btn-lg me-2",
        },
    });
};

export default BootstrapSwal;
