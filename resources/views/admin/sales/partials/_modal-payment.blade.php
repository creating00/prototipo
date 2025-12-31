<x-adminlte.dynamic-modal modalId="modalSalePayment" title="Totales y Pago" formId="formSalePayment"
    btnSaveId="btnSaveSalePayment" :localSubmit="true" :form-view="'admin.sales.partials.sections._payment'" :form-data="[
        'sale' => $sale ?? null,
        'discountOptions' => $discountOptions ?? [],
        'paymentOptions' => $paymentOptions ?? [],
        'saleDate' => $saleDate ?? now()->format('Y-m-d'),
    ]" />
