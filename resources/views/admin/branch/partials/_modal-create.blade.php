<x-adminlte.dynamic-modal modalId="modalBranch" title="Nueva Sucursal" formId="formNuevaSucursal"
    btnSaveId="btnGuardarSucursal" :route="route('branches.store')" selectId="branch_id" :form-view="'admin.branch.partials._form'" :form-data="[
        'branch' => null,
        'provinces' => $formData->provinces,
    ]" />
