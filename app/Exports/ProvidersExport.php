<?php

namespace App\Exports;

use App\Models\Provider;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProvidersExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithStyles
{
    private $rowCount = 0;

    public function collection()
    {
        $providers = Provider::all();
        $this->rowCount = $providers->count();
        return $providers;
    }

    /**
     * Cabeceras compatibles con ProvidersImport
     */
    public function headings(): array
    {
        return [
            'cuit',
            'razon_social',
            'nombre_corto',
            'contacto',
            'email',
            'telefono',
            'direccion'
        ];
    }

    /**
     * Mapeo de datos del modelo
     */
    public function map($provider): array
    {
        return [
            $provider->tax_id,
            $provider->business_name,
            $provider->short_name,
            $provider->contact_name,
            $provider->email,
            $provider->phone,
            $provider->address,
        ];
    }

    /**
     * Estilos visuales idénticos al de productos
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'G'; // De A hasta G
        $totalRows = $this->rowCount + 1;
        $range = "A1:{$lastColumn}{$totalRows}";

        // Alto de la fila de cabecera
        $sheet->getRowDimension(1)->setRowHeight(30.75);

        // Estilo de la cabecera
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

        // Centrado y bordes para todo el documento
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
}
