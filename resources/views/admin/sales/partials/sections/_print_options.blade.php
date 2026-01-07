{{-- admin/sales/partials/_print_options.blade.php --}}
<div class="text-center py-3">
    <p class="text-muted mb-4">Seleccione el formato de impresión para la venta</p>
    <div class="d-grid gap-3">
        <a href="#" id="linkPrintTicket" target="_blank" class="btn btn-outline-primary btn-lg rounded-3">
            <i class="fas fa-receipt me-2"></i> Formato Ticket (80mm)
        </a>
        <a href="#" id="linkPrintA4" target="_blank" class="btn btn-outline-secondary btn-lg rounded-3">
            <i class="fas fa-file-invoice me-2"></i> Formato A4 (Reporte)
        </a>
    </div>
</div>

<style>
    /* Ocultamos el botón de guardar por defecto del modal ya que usamos links directos */
    #btnDoPrint { display: none; }
</style>