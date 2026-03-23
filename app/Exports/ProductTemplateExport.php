<?php

namespace App\Exports;

use App\Enums\PriceType;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Str;

class ProductTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        $header = ['Codigo', 'Nombre', 'Descripcion', 'Categoria_ID', 'Stock_Inicial', 'Stock_Minimo'];

        foreach (PriceType::cases() as $type) {
            $label = $type->label();
            $header[] = "Precio {$label}";
            $header[] = "Moneda {$label}";
        }

        return $header;
    }
}
