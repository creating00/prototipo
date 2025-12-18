<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseBranchController;
use Illuminate\Http\Request;

class BranchController extends BaseBranchController
{
    public function index()
    {
        $branches = $this->branchService->getAllBranches();
        return response()->json($branches);
    }

    public function store(Request $request)
    {
        try {
            $branch = $this->branchService->createBranch($request->all());
            return response()->json($branch, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function show($id)
    {
        $branch = $this->branchService->getBranchById($id);
        return response()->json($branch);
    }

    public function update(Request $request, $id)
    {
        try {
            $branch = $this->branchService->updateBranch($id, $request->all());
            return response()->json($branch);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function destroy($id)
    {
        $this->branchService->deleteBranch($id);
        return response()->json(['message' => 'Branch deleted']);
    }
}
