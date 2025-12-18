<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BranchService
{
    public function createBranch(array $data): Branch
    {
        $validated = $this->validateBranchData($data);
        return Branch::create($validated);
    }

    public function getAllBranches()
    {
        return Branch::orderBy('name')->get();
    }

    public function getUserBranch(int $userBranchId)
    {
        return Branch::where('id', $userBranchId)
            ->orderBy('name')
            ->get();
    }

    public function getAllBranchesExcept(int $excludeBranchId)
    {
        return Branch::where('id', '!=', $excludeBranchId)
            ->orderBy('name')
            ->get();
    }

    public function getAllBranchesForDataTable()
    {
        $branches = $this->getAllBranches();

        return $branches->map(function ($branch, $index) {
            return [
                'id' => $branch->id,                         // Oculto pero usable en data-id
                'province_id' => $branch->province_id,       // Oculto si quieres
                'number' => $index + 1,                      // Columna visible #
                'name' => $branch->name,                     // Nombre de la sucursal
                'address' => $branch->address ?? '-',        // Dirección
                'province' => $branch->province->name ?? '-', // Nombre de la provincia
            ];
        })->toArray();
    }

    public function getBranchById($id): Branch
    {
        return Branch::findOrFail($id);
    }

    public function updateBranch($id, array $data): Branch
    {
        $branch = $this->getBranchById($id);
        $validated = $this->validateBranchData($data, $branch->id);

        $branch->update($validated);
        return $branch->fresh();
    }

    public function deleteBranch($id): bool
    {
        $branch = $this->getBranchById($id);
        return $branch->delete();
    }

    public function validateBranchData(array $data, $ignoreId = null): array
    {
        $rules = [
            'province_id' => 'required|exists:provinces,id',
            'name' => 'required|unique:branches,name' . ($ignoreId ? ",$ignoreId" : ''),
            'address' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
