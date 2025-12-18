<x-admin-lte.dynamic-modal modalId="modalExpenseType" title="Nuevo Tipo de Gasto" formId="formNewExpenseType"
    btnSaveId="btnGuardarExpenseType" :route="route('expense-types.store')" selectId="expense_type_id" :form-view="'admin.expense_type.partials._form'" :form-data="[
        'expenseType' => null,
    ]" />
