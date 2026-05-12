<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RepairAmount\RepairAmountWebRequest;
use App\Services\RepairAmountService;
use App\Models\RepairAmount;
use App\Models\Branch;
use App\Enums\RepairType;
use App\Traits\AuthTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RepairAmountWebController extends Controller
{
    use AuthorizesRequests, AuthTrait;

    public function __construct(
        protected RepairAmountService $repairAmountService
    ) {}

    /* =========================================================
     |  INDEX
     |=========================================================*/

    public function index()
    {
        $this->authorize('viewAny', RepairAmount::class);
        $branchId = $this->currentBranchId();

        // Obtenemos todos los registros formateados
        $allData = $this->repairAmountService->getAllForBranchForDatatable($branchId);

        // Los separamos usando colecciones de Laravel para que sea limpio
        $dataCollection = collect($allData);

        // Filtramos por el campo 'is_active_raw' (que añadiremos al service abajo)
        $activeRows = $dataCollection->where('is_active_raw', true)->values()->toArray();
        $historicalRows = $dataCollection->where('is_active_raw', false)->values()->toArray();

        $headers = [
            '#',
            'Tipo de Reparación',
            'Monto',
            'Estado',
            'Finaliza',
            'Creado'
        ];

        $hiddenFields = ['id', 'is_active_raw']; // Ocultamos el flag crudo

        return view(
            'admin.repair_amount.index',
            compact('headers', 'activeRows', 'historicalRows', 'hiddenFields')
        );
    }

    /* =========================================================
     |  CREATE
     |=========================================================*/

    public function create()
    {
        $this->authorize('create', RepairAmount::class);

        $branches = Branch::orderBy('name')->get();
        $repairTypes = RepairType::cases();

        return view(
            'admin.repair_amount.create',
            compact('branches', 'repairTypes')
        );
    }

    /* =========================================================
     |  STORE
     |=========================================================*/

    public function store(RepairAmountWebRequest $request)
    {
        $this->authorize('create', RepairAmount::class);

        try {
            $this->repairAmountService->create(
                branchId: $request->branch_id,
                repairType: RepairType::from($request->repair_type),
                amount: $request->amount,
                userId: $this->userId()
            );

            return redirect()
                ->route('web.repair-amounts.index')
                ->with('success', 'Monto de reparación creado correctamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            // El código 23000 con el nuevo índice significa que ya hay UN activo (1)
            // Esto solo pasaría si closeActive() fallara o hubiera un problema de concurrencia
            if ($e->getCode() == 23000) {
                return redirect()
                    ->back()
                    ->with('error', 'No se pudo establecer el nuevo monto. Ya existe un precio marcado como activo para este tipo de reparación.')
                    ->withInput();
            }

            return redirect()->back()->with('error', 'Error de base de datos al procesar la solicitud.')->withInput();
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Error inesperado: ' . $e->getMessage())
                ->withInput();
        }
    }

    /* =========================================================
     |  EDIT
     |=========================================================*/

    public function edit(int $id)
    {
        $repairAmount = $this->repairAmountService->findOrFail($id);

        $this->authorize('update', $repairAmount);

        $branches = Branch::orderBy('name')->get();
        $repairTypes = RepairType::cases();

        return view(
            'admin.repair_amount.edit',
            compact('repairAmount', 'branches', 'repairTypes')
        );
    }

    /* =========================================================
     |  UPDATE
     |=========================================================*/

    public function update(RepairAmountWebRequest $request, int $id)
    {
        $repairAmount = $this->repairAmountService->findOrFail($id);
        $this->authorize('update', $repairAmount);

        try {
            $this->repairAmountService->update(
                $repairAmount,
                [
                    'amount'  => $request->amount,
                    'user_id' => $this->userId(),
                    // Nota: No pasamos active_only_one aquí, 
                    // el Service se encarga de la lógica de vigencia.
                ]
            );

            return redirect()
                ->route('web.repair-amounts.index')
                ->with('success', 'Monto de reparación actualizado correctamente.');
        } catch (\Throwable $e) {
            // Agregamos un log o un mensaje más limpio
            return redirect()
                ->back()
                ->with('error', 'No se pudo actualizar el monto: ' . $e->getMessage())
                ->withInput();
        }
    }

    /* =========================================================
     |  DELETE
     |=========================================================*/

    public function destroy(int $id)
    {
        $repairAmount = $this->repairAmountService->findOrFail($id);

        $this->authorize('delete', $repairAmount);

        $this->repairAmountService->delete($repairAmount);

        return redirect()
            ->route('web.repair-amounts.index')
            ->with('success', 'Monto de reparación eliminado correctamente.');
    }
}
