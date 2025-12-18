<x-admin-lte.dynamic-modal modalId="modalClient" title="Nuevo Cliente" formId="formNuevoCliente"
    btnSaveId="btnGuardarCliente" :route="route('clients.store')" :form-view="'admin.client.partials._form'" :form-data="['client' => null]" selectId="client_id">
</x-admin-lte.dynamic-modal>
