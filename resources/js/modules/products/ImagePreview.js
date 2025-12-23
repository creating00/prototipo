// resources/js/modules/products/ImagePreview.js
export class ImagePreview {
    constructor(options = {}) {
        this.config = {
            urlInputId: "image_url",
            previewButtonId: "preview-button",
            modalImageId: "modal-preview-image",
            modalErrorId: "modal-error",
            modalId: "imagePreviewModal",
            enableTooltips: true,
            ...options,
        };

        this.urlInput = null;
        this.previewButton = null;
        this.modalImage = null;
        this.modalError = null;
        this.modalElement = null;
        this.previewTooltip = null;
        this.tooltipHideTimeout = null;
        this.eventListeners = new Map(); // Para manejar listeners
    }

    init() {
        this.cacheElements();
        this.bindEvents();
    }

    cacheElements() {
        this.urlInput = document.getElementById(this.config.urlInputId);
        this.previewButton = document.getElementById(
            this.config.previewButtonId
        );
        this.modalImage = document.getElementById(this.config.modalImageId);
        this.modalError = document.getElementById(this.config.modalErrorId);
        this.modalElement = document.getElementById(this.config.modalId);
    }

    bindEvents() {
        if (!this.config.enableTooltips) {
            return; // Saltar tooltips si están deshabilitados
        }

        if (this.previewButton) {
            this.addEventListener(this.previewButton, "click", () =>
                this.previewImageModal()
            );
        }

        if (this.urlInput) {
            // Tooltip al enfocar
            this.addEventListener(this.urlInput, "focus", () =>
                this.showTooltipOnFocus()
            );

            this.addEventListener(this.urlInput, "blur", () => {
                // Pequeño retardo para permitir clics en el tooltip
                setTimeout(() => this.safeHideTooltip(), 150);
            });

            // Ctrl+Enter para previsualizar
            this.addEventListener(this.urlInput, "keydown", (e) => {
                if (e.ctrlKey && e.key === "Enter") {
                    e.preventDefault();
                    this.previewImageModal();
                }
            });

            // Previsualización automática al escribir (con debounce)
            this.addEventListener(
                this.urlInput,
                "input",
                this.debounce(() => {
                    this.autoPreview();
                }, 500)
            );
        }
    }

    previewImageModal() {
        const url = this.urlInput?.value.trim();

        if (!url) {
            this.showAlert("Por favor, pegue una URL de imagen", "warning");
            return;
        }

        this.setButtonLoading(true);

        this.modalImage.src = url;
        this.modalError.style.display = "none";

        // Crear nuevas funciones para evitar referencias circulares
        const onLoad = () => {
            this.setButtonLoading(false);
            this.showModal();
            this.modalImage.removeEventListener("load", onLoad);
        };

        const onError = () => {
            this.setButtonLoading(false);
            this.modalError.style.display = "block";
            this.modalError.textContent =
                "No se puede cargar la imagen. Verifica que la URL sea correcta y sea una imagen.";
            this.showModal();
            this.modalImage.removeEventListener("error", onError);
        };

        this.modalImage.addEventListener("load", onLoad);
        this.modalImage.addEventListener("error", onError);
    }

    showTooltipOnFocus() {
        if (!this.config.enableTooltips || !this.urlInput) return;

        const url = this.urlInput.value.trim();
        if (url && !this.previewTooltip) {
            try {
                this.previewTooltip = new bootstrap.Tooltip(this.urlInput, {
                    title: `<div class="text-center">
                              <img src="${url}" 
                                   style="max-width: 200px; max-height: 200px;" 
                                   class="img-fluid rounded">
                            </div>`,
                    html: true,
                    placement: "top",
                    trigger: "manual",
                    customClass: "image-preview-tooltip",
                });
                this.previewTooltip.show();
            } catch (error) {
                console.warn("Error al crear tooltip:", error);
                this.previewTooltip = null;
            }
        }
    }

    safeHideTooltip() {
        if (!this.previewTooltip) return;

        try {
            // Verificar si el tooltip aún está activo
            const tooltipInstance = bootstrap.Tooltip.getInstance(
                this.urlInput
            );
            if (tooltipInstance) {
                tooltipInstance.hide();
            }
        } catch (error) {
            // Ignorar errores al ocultar tooltips
            console.debug("Tooltip ya fue eliminado");
        }

        this.previewTooltip = null;
    }

    hideTooltip() {
        if (!this.previewTooltip) return;

        // Limpiar timeout anterior
        if (this.tooltipHideTimeout) {
            clearTimeout(this.tooltipHideTimeout);
            this.tooltipHideTimeout = null;
        }

        try {
            // Método más seguro usando la API de Bootstrap
            const tooltipInstance = bootstrap.Tooltip.getInstance(
                this.urlInput
            );
            if (tooltipInstance && tooltipInstance._isShown()) {
                tooltipInstance.hide();
            }
        } catch (error) {
            if (process.env.NODE_ENV !== "production") {
                console.debug(
                    "Error al ocultar tooltip (puede ignorarse):",
                    error.message
                );
            }
        }

        this.previewTooltip = null;
    }

    autoPreview() {
        if (!this.config.enableTooltips) return;

        const url = this.urlInput?.value.trim();
        if (!url) {
            this.safeHideTooltip();
            return;
        }

        // Solo actualizar tooltip si está visible
        if (this.previewTooltip) {
            this.updateTooltipPreview(url);
        }
    }

    updateTooltipPreview(url) {
        // En lugar de destruir y recrear, actualizamos el título si es posible
        if (this.previewTooltip) {
            try {
                this.previewTooltip._config.title = `<div class="text-center">
                    <img src="${url}" 
                         style="max-width: 200px; max-height: 200px;" 
                         class="img-fluid rounded"
                         onerror="this.style.display='none'">
                </div>`;
                this.previewTooltip.update();
            } catch (error) {
                // Si falla, recreamos el tooltip
                this.recreateTooltip(url);
            }
        } else if (document.activeElement === this.urlInput && url) {
            this.recreateTooltip(url);
        }
    }

    recreateTooltip(url) {
        this.safeHideTooltip();

        if (document.activeElement === this.urlInput && url) {
            try {
                this.previewTooltip = new bootstrap.Tooltip(this.urlInput, {
                    title: `<div class="text-center">
                              <img src="${url}" 
                                   style="max-width: 200px; max-height: 200px;" 
                                   class="img-fluid rounded"
                                   onerror="this.style.display='none'">
                            </div>`,
                    html: true,
                    placement: "top",
                    trigger: "manual",
                    customClass: "image-preview-tooltip",
                });
                this.previewTooltip.show();
            } catch (error) {
                console.warn("Error al recrear tooltip:", error);
                this.previewTooltip = null;
            }
        }
    }

    showModal() {
        if (this.modalElement) {
            const modal = new bootstrap.Modal(this.modalElement);
            modal.show();
        }
    }

    useThisImage() {
        const modal = bootstrap.Modal.getInstance(this.modalElement);
        if (modal) {
            modal.hide();
        }
    }

    setButtonLoading(isLoading) {
        if (this.previewButton) {
            this.previewButton.disabled = isLoading;
            this.previewButton.innerHTML = isLoading
                ? '<i class="fas fa-spinner fa-spin"></i> Cargando...'
                : '<i class="fas fa-eye"></i>';
        }
    }

    showAlert(message, type = "info") {
        // Usar toast de Bootstrap o alert nativo
        if (typeof bootstrap !== "undefined" && bootstrap.Toast) {
            // Implementar toast si es necesario
        } else {
            alert(message);
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Helper para manejar event listeners
    addEventListener(element, event, handler) {
        if (!element) return;

        element.addEventListener(event, handler);
        const key = `${event}-${Date.now()}`;
        this.eventListeners.set(key, { element, event, handler });
    }

    // Para limpiar recursos cuando se destruya el componente
    destroy() {
        this.safeHideTooltip();

        // Remover todos los event listeners
        this.eventListeners.forEach(({ element, event, handler }) => {
            if (element && element.removeEventListener) {
                element.removeEventListener(event, handler);
            }
        });
        this.eventListeners.clear();
    }
}
