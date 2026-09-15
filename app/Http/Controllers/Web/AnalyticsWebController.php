<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Services\AnalyticsService;
use App\Traits\AuthTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AnalyticsWebController extends Controller
{
    use AuthorizesRequests;
    use AuthTrait;

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $this->authorize('view', 'analytics');

        $accessibleBranchIds = $this->getAccessibleBranchIds();
        // El contexto superior limita el alcance de todos los filtros del reporte.
        if ($this->isProvincialAdmin()) {
            $accessibleBranchIds = app(\App\Services\BranchService::class)
                ->getAccessibleBranchesForUser($request->user())->pluck('id')->all();
        }
        $contextBranchId = $this->currentBranchId();
        $contextBranchIds = $this->isProvincialAdmin() && $contextBranchId !== null
            ? array_values(array_intersect($accessibleBranchIds, [$contextBranchId]))
            : $accessibleBranchIds;

        $request->validate(['branch_id' => ['nullable', 'string', 'regex:/^(all|[1-9][0-9]*)$/']]);
        $selectedBranchId = $request->input('branch_id') ?: ($contextBranchId ?? 'all');
        if ($selectedBranchId !== 'all') {
            abort_unless(in_array((int) $selectedBranchId, $accessibleBranchIds, true), 403);
        }
        if ($this->isProvincialAdmin() && $contextBranchId !== null) {
            $selectedBranchId = $contextBranchId;
        }
        $branchId = $selectedBranchId === 'all'
            ? $contextBranchIds
            : array_values(array_intersect($contextBranchIds, [(int) $selectedBranchId]));

        // 2. Construir filtros unificados
        $filters = [
            'branch_id' => $branchId,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'category_id' => $request->input('category_id'),
            'expense_type_id' => $request->input('expense_type_id'),
            'sales_payment_type' => $request->input('sales_payment_type'),
            'sales_bank_account_id' => $request->input('sales_bank_account_id'),
            'sales_bank_id' => $request->input('sales_bank_id'),
            'expenses_expense_type_id' => $request->input('expenses_expense_type_id'),
            'expenses_payment_type' => $request->input('expenses_payment_type'),
            'expenses_bank_account_id' => $request->input('expenses_bank_account_id'),
            'balance_payment_type' => $request->input('balance_payment_type'),
            'balance_bank_account_id' => $request->input('balance_bank_account_id'),
            'balance_bank_id' => $request->input('balance_bank_id'),
        ];

        // 4. Obtener datos (El servicio ahora calcula todo, incluido resultBoxes)
        $data = $this->analyticsService->getBranchStats($filters);

        // 5. Datos para la vista
        $data['branches'] = Branch::whereIn('id', $contextBranchIds)->pluck('name', 'id');
        if (! $this->isProvincialAdmin() || $contextBranchId === null) {
            $data['branches']->prepend('Todas las sucursales disponibles (Consolidado)', 'all');
        }
        $data['categories'] = Category::pluck('name', 'id');
        $data['expenseTypes'] = \App\Models\ExpenseType::pluck('name', 'id');
        $data['banks'] = \App\Models\Bank::pluck('name', 'id');
        $data['bankAccounts'] = \App\Models\BankAccount::with(['bank', 'user'])->get()->pluck('full_description', 'id');
        $data['currentFilters'] = $filters;
        $data['currentBranchId'] = $selectedBranchId;

        return view('admin.analytics.index', $data);
    }
}
