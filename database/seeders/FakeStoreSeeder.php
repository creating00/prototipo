<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use App\Models\ProductBranch;
use App\Models\ProductBranchPrice;
use App\Enums\PriceType;
use App\Enums\CurrencyType;
use App\Enums\ProductStatus;
use Illuminate\Support\Str;

class FakeStoreSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todas las sucursales
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->error('No hay sucursales registradas.');
            return;
        }

        $response = Http::get('https://fakestoreapi.com/products');

        if ($response->failed()) {
            $this->command->error('Error al conectar con la API.');
            return;
        }

        $products = $response->json();

        foreach ($products as $item) {
            $category = Category::firstOrCreate([
                'name' => ucfirst($item['category'])
            ]);

            $product = Product::create([
                'code' => 'PROD-' . Str::upper(Str::random(6)),
                'name' => $item['title'],
                'image' => $item['image'],
                'description' => $item['description'],
                'category_id' => $category->id,
            ]);

            // Seleccionar un número aleatorio de sucursales para este producto
            // (Mínimo 1, máximo todas)
            $randomBranches = $branches->random(rand(1, $branches->count()));

            foreach ($randomBranches as $branch) {
                $productBranch = ProductBranch::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'stock' => rand(0, 50),
                    'low_stock_threshold' => 5,
                    'status' => ProductStatus::Available,
                ]);

                $priceArs = $item['price'] * 1500;
                $this->seedPrices($productBranch->id, $priceArs);
            }
        }

        $this->command->info('Productos distribuidos en sucursales exitosamente.');
    }

    private function seedPrices(int $productBranchId, float $basePrice): void
    {
        $prices = [
            ['type' => PriceType::SALE, 'factor' => 1.0],
            ['type' => PriceType::PURCHASE, 'factor' => 0.7],
            ['type' => PriceType::WHOLESALE, 'factor' => 0.85],
        ];

        foreach ($prices as $priceData) {
            ProductBranchPrice::create([
                'product_branch_id' => $productBranchId,
                'type' => $priceData['type'],
                'amount' => $basePrice * $priceData['factor'],
                'currency' => CurrencyType::ARS,
            ]);
        }
    }
}
