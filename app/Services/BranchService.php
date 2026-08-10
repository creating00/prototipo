<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Enums\RoleLabel;
use Illuminate\Support\Facades\{Validator, DB};
use Illuminate\Validation\ValidationException;

class BranchService
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function createBranch(array $data): Branch
    {
        $validated = $this->validateBranchData($data);

        return DB::transaction(function () use ($validated) {
            $branch = Branch::create($validated);

            $this->initializeBranchDefaults($branch);

            return $branch;
        });
    }

    private function initializeBranchDefaults(Branch $branch): void
    {
        $this->createDefaultClient($branch->id);
    }

    private function createDefaultClient(int $branchId): void
    {
        $this->clientService->findOrCreate([
            'document'  => config('app.default_client_document'),
            'full_name' => config('app.default_client_name'),
            'is_system' => true,
            'phone'     => '00000000',
            'address'   => 'Ciudad'
        ], $branchId);
    }

    public function getAllBranches()
    {
        return Branch::orderBy('name')->get();
    }

    public function getUserBranch(?int $userBranchId)
    {
        if (!$userBranchId) {
            return null;
        }

        return Branch::where('id', $userBranchId)
            ->orderBy('name')
            ->first();
    }

    public function getAllBranchesExcept(?int $excludeBranchId)
    {
        if (!$excludeBranchId) {
            return $this->getAllBranches();
        }

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
                'phone' => $branch->phone,
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

        // 1. Validar órdenes inter-sucursal (donde es cliente)
        if ($branch->ordersAsCustomer()->exists()) {
            throw new \Exception('No se puede eliminar: la sucursal tiene órdenes pendientes como cliente.');
        }

        // 2. Validar órdenes generadas (donde es origen)
        if ($branch->orders()->exists()) {
            throw new \Exception('No se puede eliminar: la sucursal tiene historial de órdenes registradas.');
        }

        // 3. Validar stock/productos vinculados
        if ($branch->products()->exists()) {
            throw new \Exception('No se puede eliminar: existen productos vinculados a esta sucursal.');
        }

        return $branch->delete();
    }

    public function validateBranchData(array $data, $ignoreId = null): array
    {
        $rules = [
            'province_id' => 'required|exists:provinces,id',
            'name' => 'required|unique:branches,name' . ($ignoreId ? ",$ignoreId" : ''),
            'address' => 'nullable|string',
            'phone' => 'nullable|string'
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Obtener las sucursales pertenecientes a una provincia específica.
     */
    public function getBranchesByProvince(int $provinceId)
    {
        return Branch::where('province_id', $provinceId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener las sucursales accesibles para un usuario según sus roles.
     */
    public function getAccessibleBranchesForUser(User $user)
    {
        if ($user->hasRole(RoleLabel::PROVINCIAL_ADMIN->value)) {
            $provinceId = $user->province_id ?? $user->branch?->province_id;
            if ($provinceId) {
                return Branch::where('province_id', $provinceId)->orderBy('name')->get();
            }
        }

        return collect();
    }

    /**
     * Establecer el contexto activo de sucursal en sesión con validación de seguridad.
     */
    public function setActiveBranchContext(User $user, string|int $targetBranchId): bool
    {
        if (!$user->hasRole(RoleLabel::PROVINCIAL_ADMIN->value)) {
            return false;
        }

        if ($targetBranchId === 'all') {
            session(['active_branch_id' => 'all']);
            return true;
        }

        $accessibleBranchIds = $this->getAccessibleBranchesForUser($user)->pluck('id')->toArray();
        if (in_array((int)$targetBranchId, $accessibleBranchIds, true)) {
            session(['active_branch_id' => (int)$targetBranchId]);
            return true;
        }

        return false;
    }
}
