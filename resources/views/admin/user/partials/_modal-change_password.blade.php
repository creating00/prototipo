<x-adminlte.dynamic-modal modalId="modalChangePassword" title="Seguridad: Cambiar Contraseña" formId="formChangePassword"
    btnSaveId="btnGuardarPass" :route="route('api.profile.password.update')" :form-view="'admin.user.partials._change_password'" :refreshOnSave="false">
</x-adminlte.dynamic-modal>
