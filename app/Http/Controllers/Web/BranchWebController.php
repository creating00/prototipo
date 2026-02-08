<?php

namespace App\Http\Controllers\Web;

use App\Models\Province;
use App\Http\Controllers\BaseBranchController;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchWebController extends BaseBranchController
{
    public function index()
    {
        $this->authorize('viewAny', Branch::class);
        $branches = $this->branchService->getAllBranches();
        $rowData = $this->branchService->getAllBranchesForDatatable();

        $headers = ['#', 'Sucursal', 'Teléfono', 'Dirección', 'Provincia'];
        $hiddenFields = ['id', 'province_id']; // opcional

        return view('admin.branch.index', compact('headers', 'rowData', 'hiddenFields'));
    }

    public function create()
    {
        $this->authorize('create', Branch::class);
        $provinces = Province::orderBy('name')->get();
        return view('admin.branch.create', compact('provinces'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Branch::class);
        try {
            $branch = $this->branchService->createBranch($request->all());
            return redirect()->route('web.branches.index')
                ->with('success', 'Sucursal creada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function edit($id)
    {
        $branch = $this->branchService->getBranchById($id);
        $this->authorize('update', $branch);
        $provinces = Province::orderBy('name')->get();

        return view('admin.branch.edit', compact('branch', 'provinces'));
    }

    public function update(Request $request, $id)
    {
        $branch = $this->branchService->getBranchById($id);
        $this->authorize('update', $branch);
        try {
            $this->branchService->updateBranch($id, $request->all());
            return redirect()->route('web.branches.index')
                ->with('success', 'Sucursal actualizada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $branch = $this->branchService->getBranchById($id);
        $this->authorize('delete', $branch);

        try {
            $this->branchService->deleteBranch($id);

            return redirect()->route('web.branches.index')
                ->with('success', 'Sucursal eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
