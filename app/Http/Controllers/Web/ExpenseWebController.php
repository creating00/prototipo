<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\BaseExpenseController;
use App\Http\Requests\Expense\ExpenseWebRequest;
use App\Models\Branch;
use App\Models\Expense;
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
        $this->authorize('viewAny', Expense::class);

        $currentBranchId = $this->currentBranchId();

        $rowData = $dataTableService->getAllExpensesForDataTable($currentBranchId);

        $headers = [
            '#',
            'Sucursal',
            'Fecha',
            'Monto',
            'Forma de pago',
            'Motivo'
        ];

        $hiddenFields = ['id', 'branch-id', 'payment_type_raw', 'currency', 'amount_raw', 'bank_account_id'];

        $currentBranchId = $this->currentBranchId();
        $branches = Branch::pluck('name', 'id');
        $bankAccounts = \App\Models\BankAccount::with(['bank', 'user'])
            ->get()
            ->pluck('full_description', 'id');

        return view('admin.expense.index', compact('headers', 'rowData', 'hiddenFields', 'currentBranchId', 'branches', 'bankAccounts'));
    }

    public function create()
    {
        if ($redirect = $this->denyIfConsolidatedMutation('web.expenses.index')) {
            return $redirect;
        }

        $this->authorize('create', Expense::class);
        $branchUserId = $this->currentBranchId();
        $bankAccounts = \App\Models\BankAccount::with(['bank', 'user'])->get()->pluck('full_description', 'id')->toArray();

        $formData = new ExpenseFormData(
            expense: null,
            branches: app(\App\Services\BranchService::class)->getAllBranches(),
            expenseTypes: app(\App\Services\ExpenseTypeService::class)->getAllExpenseTypes(),
            provinces: Province::orderBy('name')->get(),
            currencyOptions: \App\Enums\CurrencyType::forSelect(),
            paymentOptions: \App\Enums\PaymentType::forSelect(),
            branchUserId: $branchUserId,
            bankAccounts: $bankAccounts,
        );

        return view('admin.expense.create', compact('formData'));
    }

    public function store(ExpenseWebRequest $request)
    {
        if ($redirect = $this->denyIfConsolidatedMutation('web.expenses.index')) {
            return $redirect;
        }

        $this->authorize('create', Expense::class);

        $data = $request->validated();

        $resolvedBranchId = $data['branch_id']
            ?? $this->currentBranchId()
            ?? $this->currentUser()?->branch_id
            ?? ($this->getAccessibleBranchIds()[0] ?? Branch::first()?->id);

        // Mapeo de campos del componente y auditoría
        $data['user_id']   = $this->userId();
        $data['branch_id'] = $resolvedBranchId;
        $data['amount']   = $data['amount_amount'];
        $data['currency'] = $data['amount_currency'];

        if ((int)$data['payment_type'] !== 3) {
            $data['bank_account_id'] = null;
        }

        $this->expenseService->createExpense($data);

        return redirect()->route('web.expenses.index')
            ->with('success', 'Gasto registrado correctamente');
    }

    public function edit($id)
    {
        $expense = $this->expenseService->getExpenseById($id);

        $accessibleBranchIds = $this->getAccessibleBranchIds();
        if (!empty($accessibleBranchIds) && !in_array($expense->branch_id, $accessibleBranchIds)) {
            return redirect()
                ->route('web.expenses.index')
                ->withErrors('No tienes permiso para editar gastos de otra sucursal.');
        }

        $this->authorize('update', $expense);

        //dd($expense);

        $branchUserId = $this->currentBranchId();
        $bankAccounts = \App\Models\BankAccount::with(['bank', 'user'])->get()->pluck('full_description', 'id')->toArray();

        $formData = new \App\ViewModels\ExpenseFormData(
            expense: $expense,
            branches: app(\App\Services\BranchService::class)->getAllBranches(),
            expenseTypes: app(\App\Services\ExpenseTypeService::class)->getAllExpenseTypes(),
            provinces: Province::orderBy('name')->get(),
            currencyOptions: \App\Enums\CurrencyType::forSelect(),
            paymentOptions: \App\Enums\PaymentType::forSelect(),
            branchUserId: $branchUserId,
            bankAccounts: $bankAccounts,
        );

        return view('admin.expense.edit', compact('formData'));
    }

    public function update(ExpenseWebRequest $request, $id)
    {
        $expense = $this->expenseService->getExpenseById($id);
        $this->authorize('update', $expense);

        $data = $request->validated();

        // Mapeo consistente con store
        $data['user_id']  = $this->userId();
        $data['amount']   = $data['amount_amount'];
        $data['currency'] = $data['amount_currency'];

        if ((int)$data['payment_type'] !== 3) {
            $data['bank_account_id'] = null;
        }

        $this->expenseService->updateExpense($id, $data);

        return redirect()->route('web.expenses.index')
            ->with('success', 'Gasto actualizado correctamente');
    }

    public function destroy($id)
    {
        $expense = $this->expenseService->getExpenseById($id);

        $this->authorize('delete', $expense);

        $this->expenseService->deleteExpense($id);
        return redirect()->route('admin.branch.index')
            ->with('success', 'Gasto eliminado correctamente');
    }
}
