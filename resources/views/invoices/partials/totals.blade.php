<div class="totals-section">
    <div class="totals-left">
        <div class="notes-box">
            <div class="notes-title">Notas</div>
            <div class="notes-text">
                Gracias por su preferencia. El pago debe realizarse dentro de los 30 días de la fecha de facturación. 
                En caso de consultas, no dude en contactarnos.
            </div>
        </div>
    </div>
    
    <div class="totals-right">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td>${{ number_format($totals['subtotal'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>IVA ({{ $totals['tax_rate'] }}%):</td>
                <td>${{ number_format($totals['tax'], 2, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL:</td>
                <td>${{ number_format($totals['total'], 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</div>