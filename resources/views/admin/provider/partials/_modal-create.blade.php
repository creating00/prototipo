<x-adminlte.dynamic-modal modalId="modalProvider" title="Nuevo Proveedor" formId="formNewProvider"
    btnSaveId="btnSaveProvider" :route="route('providers.store')" selectId="expense_type_id" :form-view="'admin.provider.partials._form'" successEvent="provider:created" :form-data="[
        'provider' => null,
    ]" />
