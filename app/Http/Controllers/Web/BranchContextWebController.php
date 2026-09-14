<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\BranchService;
use Illuminate\Http\Request;

class BranchContextWebController extends Controller
{
    public function switchContext(Request $request, BranchService $branchService)
    {
        $request->validate([
            'branch_id' => 'required|string',
        ]);

        $targetBranchId = $request->input('branch_id');
        $success = $branchService->setActiveBranchContext($request->user(), $targetBranchId);

        if (! $success) {
            return back()->with('error', 'No tiene permisos para acceder al contexto de esa sucursal o provincia.');
        }

        session()->forget('analytics_branch_id');

        // El enlace anterior puede conservar una sucursal del formulario analítico.
        $previousUrl = url()->previous();
        if (parse_url($previousUrl, PHP_URL_PATH) === parse_url(route('web.analytics.index'), PHP_URL_PATH)) {
            parse_str(parse_url($previousUrl, PHP_URL_QUERY) ?? '', $filters);
            unset($filters['branch_id']);

            return redirect()->route('web.analytics.index', $filters)
                ->with('success', 'Contexto de sucursal actualizado correctamente.');
        }

        return back()->with('success', 'Contexto de sucursal actualizado correctamente.');
    }
}
