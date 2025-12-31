<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Discount\DiscountWebRequest;
use App\Models\Discount;
use App\Services\DiscountService;
use Exception;

class DiscountWebController extends Controller
{
    public function __construct(
        protected DiscountService $discountService
    ) {}

    public function index()
    {
        // Obtenemos los datos ya formateados desde el servicio
        $rowData = $this->discountService->getAllDiscountsForDataTable();

        // Encabezados que coinciden con las llaves de rowData (excepto los ocultos)
        $headers = ['#', 'Nombre', 'Tipo', 'Valor', 'Estado'];

        // Definimos qué campos queremos que existan en el DOM (data-attributes) pero no en columnas
        $hiddenFields = ['id'];

        return view('admin.discount.index', compact('headers', 'rowData', 'hiddenFields'));
    }

    public function create()
    {
        return view('admin.discount.create');
    }

    public function store(DiscountWebRequest $request)
    {
        try {
            $this->discountService->create($request->validated());
            return redirect()->route('web.discounts.index')
                ->with('success', 'Descuento creado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'No se pudo crear el descuento.'])
                ->withInput();
        }
    }

    public function edit(Discount $discount)
    {
        return view('admin.discount.edit', compact('discount'));
    }

    public function update(DiscountWebRequest $request, Discount $discount)
    {
        try {
            $this->discountService->update($discount, $request->validated());
            return redirect()->route('web.discounts.index')
                ->with('success', 'Descuento actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'No se pudo actualizar el descuento.'])
                ->withInput();
        }
    }

    public function destroy(Discount $discount)
    {
        $this->discountService->delete($discount);
        return redirect()->route('web.discounts.index')
            ->with('success', 'Descuento eliminado exitosamente');
    }
}
