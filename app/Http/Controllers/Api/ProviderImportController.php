<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\ProvidersImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ProviderImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new ProvidersImport, $request->file('file'));

            return response()->json([
                'message' => 'Importación de proveedores finalizada con éxito.'
            ], 200);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            return response()->json([
                'error' => 'Error de formato en el Excel de proveedores',
                'details' => $failures
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error importando proveedores: " . $e->getMessage());
            return response()->json([
                'error' => 'Ocurrió un error inesperado durante la importación',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/public/templates/plantilla_proveedores.xlsx');
        if (!file_exists($path)) {
            return abort(404, 'La plantilla no existe en el servidor.');
        }
        return response()->download($path, 'plantilla_importacion_proveedores.xlsx');
    }
}
