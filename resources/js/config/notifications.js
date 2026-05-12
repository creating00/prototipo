// Configuración para notificaciones rápidas (Toasts)
export const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    // Estilos de AdminLTE / Bootstrap
    customClass: {
        popup: "colored-toast",
    },
    didOpen: (toast) => {
        toast.addEventListener("mouseenter", Swal.stopTimer);
        toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
});

// Configuración para alertas de confirmación (Modales de borrado, etc.)
export const ConfirmDialog = Swal.mixin({
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, continuar",
    cancelButtonText: "Cancelar",
    reverseButtons: true,
});
