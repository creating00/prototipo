{{-- resources/views/admin/order/partials/_convert_to_sale_modal.blade.php --}}

<x-adminlte.dynamic-modal modalId="convertOrderModal" title="Convertir Orden a Venta" formId="convertOrderForm"
    btnSaveId="btnConfirmConvert" route="#" :form-view="'admin.order.partials._convert_form'" :form-data="[
        'paymentTypes' => \App\Enums\PaymentType::forSelect(),
    ]" :refreshOnSave="true" />
