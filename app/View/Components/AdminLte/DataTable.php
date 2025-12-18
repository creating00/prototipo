<?php

namespace App\View\Components\AdminLte;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DataTable extends Component
{
    public string $tableId;
    public string $title;
    public string $size;
    public array $headers;
    public array $rows;
    public bool $responsive;
    public bool $withActions;
    public array $rowData;
    public array $hiddenFields;

    public function __construct(
        string $tableId,
        string $title = 'DataTable',
        string $size = 'main',
        array $headers = [],
        array $rowData = [],
        bool $responsive = true,
        bool $withActions = false,
        array $hiddenFields = []
    ) {
        $this->tableId = $tableId;
        $this->title = $title;
        $this->size = $size;
        $this->headers = $headers;
        $this->rowData = $rowData;
        $this->responsive = $responsive;
        $this->withActions = $withActions;
        $this->hiddenFields = $hiddenFields ?: $this->detectHiddenFields($rowData);

        $this->rows = $this->generateRowsFromRowData();

        if (!in_array($this->size, ['main', 'sm'])) {
            $this->size = 'main';
        }
    }

    protected function detectHiddenFields(array $rowData): array
    {
        $fields = [];

        if (!empty($rowData)) {
            $firstRow = $rowData[0];

            foreach ($firstRow as $key => $value) {
                if ($key === 'id' || str_ends_with($key, '_id')) {
                    $fields[] = $key;
                }
            }
        }

        return $fields;
    }

    protected function generateRowsFromRowData(): array
    {
        return array_map(function ($item) {
            // Filtrar los campos ocultos para no mostrarlos en las celdas
            $visibleData = array_filter($item, function ($key) {
                return !in_array($key, $this->hiddenFields);
            }, ARRAY_FILTER_USE_KEY);

            // Procesar cada valor para manejar nulos/vacíos
            $processedData = [];
            foreach ($visibleData as $key => $value) {
                $processedData[$key] = $this->formatCellValue($value);
            }

            return array_values($processedData);
        }, $this->rowData);
    }

    protected function formatCellValue($value): string
    {
        // Si es nulo o vacío, mostrar "Sin dato"
        if (is_null($value) || $value === '' || $value === []) {
            return '<small class="text-muted"><i>Sin dato</i></small>';
        }

        // Si ya es un string que contiene HTML (como "<small>Sin dato</small>"), devolverlo tal cual
        if (is_string($value) && preg_match('/<[^>]+>/', $value)) {
            return $value;
        }

        // Si es booleano, mostrar Sí/No
        if (is_bool($value)) {
            $badgeClass = $value ? 'success' : 'secondary';
            $text = $value ? 'Sí' : 'No';
            return '<span class="badge bg-' . $badgeClass . '">' . $text . '</span>';
        }

        // Si es una fecha Carbon
        if ($value instanceof \Carbon\Carbon) {
            return '<span title="' . e($value->format('Y-m-d H:i:s')) . '">' .
                e($value->format('d/m/Y')) .
                '</span>';
        }

        // Si es una fecha DateTime
        if ($value instanceof \DateTimeInterface) {
            return e($value->format('d/m/Y'));
        }

        // Si es un array, convertir a string
        if (is_array($value)) {
            return e(implode(', ', $value));
        }

        // Si es un objeto con método __toString()
        if (is_object($value) && method_exists($value, '__toString')) {
            return e($value->__toString());
        }

        // Devolver el valor como string, escapando HTML por seguridad
        return e((string) $value);
    }

    public function render(): View|Closure|string
    {
        return view('components.admin-lte.data-table');
    }

    public function getTableClass(): string
    {
        return "table table-bordered table-striped table-hover datatable-{$this->size}";
    }

    public function getRowData($index)
    {
        return $this->rowData[$index] ?? [];
    }
}
