<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePreviewModalLabel">Vista previa de imagen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="modal-preview-container">
                    <img id="modal-preview-image" src="" alt="Vista previa" class="img-fluid rounded"
                        style="max-height: 70vh;">
                </div>
                <div id="modal-error" class="alert alert-danger mt-3" style="display: none;">
                    No se puede cargar la imagen. Verifica la URL.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="useThisImage()">Usar esta imagen</button>
            </div>
        </div>
    </div>
</div>