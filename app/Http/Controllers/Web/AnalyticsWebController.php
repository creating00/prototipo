<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{Branch, Category};
use App\Services\AnalyticsService;
use App\Traits\AuthTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AnalyticsWebController extends Controller
{
    use AuthTrait;
    use AuthorizesRequests;

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $this->authorize('view', 'analytics');

        // 1. Determinar Branch ID
        $branchId = $request->input('branch_id')
            ?? session('analytics_branch_id')
            ?? $this->currentBranchId();

        // 2. Construir filtros unificados
        $filters = [
            'branch_id'   => $branchId,
            'start_date'  => $request->input('start_date'),
            'end_date'    => $request->input('end_date'),
            'category_id' => $request->input('category_id'),
        ];

        // 3. Persistir sesión
        if ($request->filled('branch_id')) {
            session(['analytics_branch_id' => $branchId]);
        }

        // 4. Obtener datos (El servicio ahora calcula todo, incluido resultBoxes)
        $data = $this->analyticsService->getBranchStats($filters);

        // 5. Datos para la vista
        $data['branches'] = Branch::pluck('name', 'id');
        $data['categories'] = Category::pluck('name', 'id');
        $data['currentFilters'] = $filters;
        $data['currentBranchId'] = $branchId;

        return view('admin.analytics.index', $data);
    }
}
