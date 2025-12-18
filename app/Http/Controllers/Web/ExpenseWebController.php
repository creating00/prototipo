<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseExpenseController;
use App\Http\Requests\Expense\ExpenseWebRequest;
use App\Models\Province;
use App\Models\User;
use App\Services\Expense\ExpenseDataTableService;
use App\Traits\AuthTrait;
use App\ViewModels\ExpenseFormData;
use Illuminate\Http\Request;

class ExpenseWebController extends BaseExpenseController
{
    use AuthTrait;

    public function index(ExpenseDataTableService $dataTableService)
    {
        $rowData = $dataTableService->getAllExpensesForDataTable();

        // Encabezados en español, alineados con las claves del map
        $headers = [
            '#',
            'Usuario',
            'Sucursal',
            'Tipo de gasto',
            'Monto',
            'Forma de pago',
            'Referencia',
            'Fecha de registro'
        ];

        // Campos ocultos que no se muestran en la tabla pero pueden ser útiles
        $hiddenFields = ['id'];

        return view('admin.expense.index', compact('headers', 'rowData', 'hiddenFields'));
    }

    public function create()
    {
        $branchUserId = $this->currentBranchId();

        $formData = new ExpenseFormData(
            expense: null,
            branches: app(\App\Services\BranchService::class)->getAllBranches(),
            expenseTypes: app(\App\Services\ExpenseTypeService::class)->getAllExpenseTypes(),
            provinces: Province::orderBy('name')->get(),
            currencyOptions: \App\Enums\CurrencyType::forSelect(),
            paymentOptions: \App\Enums\PaymentType::forSelect(),
            branchUserId: $branchUserId,
        );

        return view('admin.expense.create', compact('formData'));
    }

    public function store(ExpenseWebRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $this->userId();
        $data['amount'] = $data['amount_amount'];
        $data['currency'] = $data['amount_currency'];

        $this->expenseService->createExpense($data);
        return redirect()->route('admin.expense.index')
            ->with('success', 'Gasto registrado correctamente');
    }

    public function edit($id)
    {
        $branchUserId = $this->currentBranchId();

        $expense = $this->expenseService->getExpenseById($id);

        $formData = new \App\ViewModels\ExpenseFormData(
            expense: $expense,
            branches: app(\App\Services\BranchService::class)->getAllBranches(),
            expenseTypes: app(\App\Services\ExpenseTypeService::class)->getAllExpenseTypes(),
            provinces: Province::orderBy('name')->get(),
            currencyOptions: \App\Enums\CurrencyType::forSelect(),
            paymentOptions: \App\Enums\PaymentType::forSelect(),
            branchUserId: $branchUserId,
        );

        return view('admin.expense.edit', compact('formData'));
    }

    public function update(ExpenseWebRequest $request, $id)
    {
        $data = $request->validated();
        $data['user_id'] = $this->userId();
        $data['amount'] = $data['amount_amount'];
        $data['currency'] = $data['amount_currency'];

        $this->expenseService->updateExpense($id, $data);

        return redirect()->route('admin.expense.index')
            ->with('success', 'Gasto actualizado correctamente');
    }

    public function destroy($id)
    {
        $this->expenseService->deleteExpense($id);
        return redirect()->route('admin.branch.index')
            ->with('success', 'Gasto eliminado correctamente');
    }
}
