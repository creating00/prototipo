<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\ExpensesImport;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ExpenseImportController extends Controller
{
    use AuthTrait;

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $branchId = $this->currentBranchId();

        if (!$branchId) {
            return response()->json([
                'error' => 'No se pudo determinar la sucursal activa.'
            ], 400);
        }

        try {
            Excel::import(new ExpensesImport($branchId), $request->file('file'));

            return response()->json([
                'message' => 'Importación de gastos finalizada con éxito.'
            ], 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            return response()->json([
                'error' => 'Error de validación en el Excel',
                'details' => $failures
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error importando gastos: " . $e->getMessage());
            return response()->json([
                'error' => 'Error inesperado al procesar los gastos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/public/templates/plantilla_gastos.xlsx');

        if (!file_exists($path)) {
            return response()->json([
                'error' => 'La plantilla de gastos no se encuentra disponible.'
            ], 404);
        }

        return response()->download($path, 'plantilla_importacion_gastos.xlsx');
    }
}
