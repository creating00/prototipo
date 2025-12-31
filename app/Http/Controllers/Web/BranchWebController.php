<?php

namespace App\Http\Controllers\Web;

use App\Models\Province;
use App\Http\Controllers\BaseBranchController;
use Illuminate\Http\Request;

class BranchWebController extends BaseBranchController
{
    public function index()
    {
        $branches = $this->branchService->getAllBranches();
        $rowData = $this->branchService->getAllBranchesForDatatable();

        $headers = ['#', 'Sucursal', 'Teléfono', 'Dirección', 'Provincia'];
        $hiddenFields = ['id', 'province_id']; // opcional

        return view('admin.branch.index', compact('headers', 'rowData', 'hiddenFields'));
    }

    public function create()
    {
        $provinces = Province::orderBy('name')->get();
        return view('admin.branch.create', compact('provinces'));
    }

    public function store(Request $request)
    {
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
        $provinces = Province::orderBy('name')->get();

        return view('admin.branch.edit', compact('branch', 'provinces'));
    }

    public function update(Request $request, $id)
    {
        try {
            $branch = $this->branchService->updateBranch($id, $request->all());
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
        $this->branchService->deleteBranch($id);
        return redirect()->route('web.branches.index')
            ->with('success', 'Sucursal eliminada exitosamente');
    }
}
