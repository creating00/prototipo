<x-adminlte.dynamic-modal modalId="modalClient" title="Nuevo Cliente" formId="formNuevoCliente"
    btnSaveId="btnGuardarCliente" :route="route('clients.store')" :form-view="'admin.client.partials._form'" :form-data="['client' => null, 'branch_id' => $currentBranchId]" selectId="client_id">
</x-adminlte.dynamic-modal>
