<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Provider;
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Enums\PriceType;
use App\Enums\CurrencyType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ProductsImport implements ToModel, WithHeadingRow
{
    private $branchId;
    private $productService;
    private $categoryService;

    public function __construct($branchId)
    {
        $this->branchId = $branchId;
        $this->productService = app(ProductService::class);
        $this->categoryService = app(CategoryService::class);
    }

    public function model(array $row)
    {
        if (!isset($row['codigo']) || empty($row['codigo'])) return null;

        $categoryId = $this->resolveCategory($row['categoria'] ?? null);
        $dynamicPrices = $this->processPrices($row);

        // Resolvemos los IDs de los proveedores a partir de los CUITs
        $providerIds = $this->resolveProviders($row['proveedores_cuit'] ?? null);

        $data = [
            'code'                => (string)$row['codigo'],
            'name'                => $row['nombre'] ?? 'Sin nombre',
            'description'         => $row['descripcion'] ?? null,
            'category_id'         => $categoryId,
            'branch_id'           => $this->branchId,
            'stock'               => $row['stock_inicial'] ?? 0,
            'low_stock_threshold' => $row['stock_minimo'] ?? 0,
            'status'              => 1,
            'providers'           => $providerIds,
        ];

        foreach ($dynamicPrices as $p) {
            $type = PriceType::from($p['type']);
            $key = strtolower($type->name);
            $data["{$key}_price_amount"] = $p['amount'];
            $data["{$key}_price_currency"] = $p['currency'];
        }

        $data['purchase_price_amount'] = $data['purchase_price_amount'] ?? 0;
        $data['purchase_price_currency'] = $data['purchase_price_currency'] ?? CurrencyType::ARS->value;
        $data['sale_price_amount'] = $data['sale_price_amount'] ?? 0;
        $data['sale_price_currency'] = $data['sale_price_currency'] ?? CurrencyType::ARS->value;

        $existingProduct = Product::where('code', $row['codigo'])->first();

        return $existingProduct
            ? $this->productService->update($existingProduct, $data)
            : $this->productService->create($data);
    }

    /**
     * Convierte string de CUITs separados por coma en array de IDs
     */
    private function resolveProviders(?string $cuits): array
    {
        if (empty(trim($cuits ?? ''))) return [];

        // Separar por comas, limpiar espacios y filtrar vacíos
        $cuitsArray = array_filter(array_map('trim', explode(',', $cuits)));

        if (empty($cuitsArray)) return [];

        // Buscamos solo los proveedores que existan en la DB
        return Provider::whereIn('tax_id', $cuitsArray)
            ->pluck('id')
            ->toArray();
    }

    private function resolveCategory(?string $name): ?int
    {
        if (empty(trim($name ?? ''))) return null;

        $category = Category::where('name', trim($name))->first();

        if (!$category) {
            $category = $this->categoryService->createCategory(['name' => trim($name)]);
        }

        return $category->id;
    }

    private function processPrices(array $row): array
    {
        $prices = [];
        foreach (PriceType::cases() as $type) {
            $labelSlug = Str::slug($type->label(), '_');
            $priceCol = "precio_{$labelSlug}";
            $currencyCol = "moneda_{$labelSlug}";

            if (isset($row[$priceCol]) && is_numeric($row[$priceCol])) {
                $prices[] = [
                    'type'     => $type->value,
                    'amount'   => (float) $row[$priceCol],
                    'currency' => $this->parseCurrencyValue($row[$currencyCol] ?? 'ARS'),
                ];
            }
        }
        return $prices;
    }

    private function parseCurrencyValue(?string $value): int
    {
        $value = strtoupper(trim($value ?? 'ARS'));
        return ($value === 'USD') ? CurrencyType::USD->value : CurrencyType::ARS->value;
    }
}
