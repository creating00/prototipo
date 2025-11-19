<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return Branch::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:branches',
            'address' => 'nullable'
        ]);

        return Branch::create($validated);
    }

    public function show(Branch $branch)
    {
        return $branch;
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|unique:branches,name,' . $branch->id,
            'address' => 'nullable'
        ]);

        $branch->update($validated);
        return $branch;
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return response()->json(['message' => 'Branch deleted']);
    }
}
