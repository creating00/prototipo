<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ApiProductImportController extends Controller
{
    public function importFromApi()
    {
        try {
            // Obtener datos de la API
            $response = Http::get('https://fakestoreapi.com/products');

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener datos de la API'
                ], 500);
            }

            $apiProducts = $response->json();

            // Verificar que tenemos al menos una sucursal y categoría
            $defaultBranch = Branch::first();
            if (!$defaultBranch) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay sucursales configuradas. Crea al menos una sucursal primero.'
                ], 400);
            }

            $importedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($apiProducts as $apiProduct) {
                try {
                    // Buscar o crear la categoría
                    $category = $this->getOrCreateCategory($apiProduct['category']);

                    // Generar código único
                    $code = $this->generateUniqueCode($apiProduct['title']);

                    // Crear el producto
                    Product::create([
                        'code' => $code,
                        'name' => $apiProduct['title'],
                        'description' => $apiProduct['description'],
                        'image' => $apiProduct['image'],
                        'stock' => rand(10, 100), // Stock aleatorio o puedes usar un valor fijo
                        'branch_id' => $defaultBranch->id,
                        'category_id' => $category->id,
                        'purchase_price' => $apiProduct['price'],
                        'sale_price' => $apiProduct['price'],
                    ]);

                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'product' => $apiProduct['title'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Importación completada. {$importedCount} productos importados.",
                'imported_count' => $importedCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error en la importación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener o crear categoría
     */
    private function getOrCreateCategory($categoryName)
    {
        return Category::firstOrCreate(
            ['name' => ucfirst($categoryName)],
            ['description' => "Categoría: {$categoryName}"]
        );
    }

    /**
     * Generar código único basado en el título
     */
    private function generateUniqueCode($title)
    {
        $baseCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $title), 0, 8));
        $code = $baseCode;
        $counter = 1;

        // Verificar si el código ya existe y generar uno único
        while (Product::where('code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Método para verificar la conexión con la API
     */
    public function checkApi()
    {
        try {
            $response = Http::get('https://fakestoreapi.com/products');

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'product_count' => $response->successful() ? count($response->json()) : 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
