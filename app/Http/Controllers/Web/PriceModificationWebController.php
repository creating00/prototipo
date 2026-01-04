<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{Branch, PriceModification, User};
use App\Traits\AuthTrait;
use Illuminate\Http\Request;

class PriceModificationWebController extends Controller
{
    use AuthTrait;

    public function index(Request $request)
    {
        $branchId = $request->input('branch_id') ?? $this->currentBranchId();

        $query = PriceModification::with(['product', 'user', 'branch', 'sale'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($request->filled('start_date'), fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->latest();

        $modifications = $query->get();

        // Preparar datos para el componente data-table
        $headers = ['Fecha', 'Usuario', 'Producto', 'P. Original', 'P. Modificado', 'Diferencia', 'Motivo'];

        $rowData = $modifications->map(fn($item) => [
            'id'             => $item->id,
            'Fecha'          => $item->created_at->format('d/m/Y H:i'),
            'Usuario'        => $item->user->name ?? 'Sistema',
            'Producto'       => $item->product->name,
            'P. Original'    => '$' . number_format($item->original_price, 2),
            'P. Modificado'  => '$' . number_format($item->modified_price, 2),
            'Diferencia'     => '$' . number_format($item->modified_price - $item->original_price, 2),
            'Motivo'         => $item->reason,
        ]);

        return view('admin.price-modifications.index', [
            'branches'       => Branch::pluck('name', 'id'),
            'currentFilters' => [
                'branch_id'  => $branchId,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
            ],
            'headers'        => $headers,
            'rowData'        => $rowData,
            'hiddenFields'   => ['id']
        ]);
    }
}
