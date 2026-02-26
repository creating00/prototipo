<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\ProductsImport;
use App\Traits\AuthTrait;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ProductImportController extends Controller
{
    use AuthTrait;

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Máx 10MB
        ]);

        // Obtenemos el branchId de la sesión o request según tu lógica
        $branchId = $this->currentBranchId();

        if (!$branchId) {
            return response()->json([
                'error' => 'No se pudo determinar la sucursal activa. Revisa tu sesión.'
            ], 400);
        }

        try {
            Excel::import(new ProductsImport($branchId), $request->file('file'));

            return response()->json([
                'message' => 'Importación finalizada con éxito.'
            ], 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            return response()->json([
                'error' => 'Error de formato en el Excel',
                'details' => $failures
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error importando productos: " . $e->getMessage());
            return response()->json([
                'error' => 'Ocurrió un error inesperado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/public/templates/plantilla_productos.xlsx');
        if (!file_exists($path)) {
            return abort(404, 'La plantilla no existe en el servidor.');
        }
        return response()->download($path, 'plantilla_importacion_productos.xlsx');
    }
}
