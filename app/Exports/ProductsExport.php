<?php

namespace App\Exports;

use App\Models\Product;
use App\Enums\PriceType;
use App\Enums\CurrencyType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductsExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    private $branchId;
    private $rowCount = 0;

    public function __construct($branchId)
    {
        $this->branchId = $branchId;
    }

    public function collection()
    {
        $products = Product::with([
            'category',
            'providers',
            'productBranches' => function ($q) {
                $q->where('branch_id', $this->branchId)->with('prices');
            }
        ])
            ->whereHas('productBranches', function ($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->get();

        $this->rowCount = $products->count();
        return $products;
    }

    public function headings(): array
    {
        return [
            'codigo',
            'nombre',
            'descripcion',
            'categoria',
            'stock_inicial',
            'stock_minimo',
            'precio_compra',
            'moneda_compra',
            'precio_venta',
            'moneda_venta',
            'precio_mayorista',
            'moneda_mayorista',
            'precio_reparacion',
            'moneda_reparacion',
            'proveedores_cuit'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'O';
        $totalRows = $this->rowCount + 1;
        $range = "A1:{$lastColumn}{$totalRows}";

        // 1. AJUSTE DE ALTO DE FILA (Cabecera)
        $sheet->getRowDimension(1)->setRowHeight(30.75);

        // 2. Estilo para la cabecera (Negrita, Centrado, Color de fondo)
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E2E2'],
            ],
        ]);

        // 3. Aplicar a todo el rango: Bordes y Centrado
        return [
            $range => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function map($product): array
    {
        $branchData = $product->productBranches->first();

        $data = [
            $product->code,
            $product->name,
            $product->description,
            $product->category?->name,
            $branchData?->stock ?? 0,
            $branchData?->low_stock_threshold ?? 0,
        ];

        $types = [
            PriceType::PURCHASE,
            PriceType::SALE,
            PriceType::WHOLESALE,
            PriceType::REPAIR
        ];

        foreach ($types as $type) {
            $priceNode = $branchData ? $branchData->prices->where('type', $type->value)->first() : null;
            $data[] = $priceNode ? $priceNode->amount : 0;
            $data[] = $priceNode ? ($priceNode->currency == CurrencyType::USD->value ? 'USD' : 'ARS') : 'ARS';
        }

        $data[] = $product->providers->pluck('tax_id')->filter()->implode(',');

        return $data;
    }
}
