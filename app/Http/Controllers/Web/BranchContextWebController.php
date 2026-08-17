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

        if (!$success) {
            return back()->with('error', 'No tiene permisos para acceder al contexto de esa sucursal o provincia.');
        }

        return back()->with('success', 'Contexto de sucursal actualizado correctamente.');
    }
}
