<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    /**
     * Listar todas las provincias (opcionalmente con sus sucursales)
     */
    public function index()
    {
        // Traemos todas las provincias
        $provinces = Province::all();
        return response()->json($provinces);
    }

    /**
     * Mostrar una provincia específica con todas sus sucursales
     */
    public function show($id)
    {
        // Buscamos la provincia y cargamos su relación 'branches'
        $province = Province::with('branches')->find($id);

        if (!$province) {
            return response()->json(['message' => 'Provincia no encontrada'], 404);
        }

        return response()->json($province);
    }
}
