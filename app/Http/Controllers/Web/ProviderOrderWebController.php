<?php

namespace App\Http\Controllers\Web;

use App\Enums\CurrencyType;
use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProviderOrder;
use App\Models\ProviderProduct;
use App\Services\ProviderOrderService;
use App\Services\ProviderService;
use App\Enums\ProviderOrderStatus;
use App\Http\Requests\ProviderOrder\ProviderOrderWebRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProviderOrderWebController extends Controller
{
    protected ProviderOrderService $orderService;
    protected ProviderService $providerService;

    public function __construct(
        ProviderOrderService $orderService,
        ProviderService $providerService
    ) {
        $this->orderService = $orderService;
        $this->providerService = $providerService;
    }

    public function index()
    {
        $orders = ProviderOrder::with(['provider', 'items'])->latest()->get();
        $headers = ['#', 'Nro. Orden', 'Proveedor', 'Fecha Pedido', 'Entrega Est.', 'Estado', 'Total Est.'];

        $rowData = $orders->map(function ($order, $index) {
            return [
                'id' => $order->id,
                'status_raw' => $order->status->value,
                'row_number' => $index + 1,
                'order_id_text' => "#ORD-" . str_pad($order->id, 5, '0', STR_PAD_LEFT),
                'provider' => $order->provider->business_name,
                'order_date' => $order->order_date->format('d/m/Y'),
                'expected_delivery_date' => $order->expected_delivery_date?->format('d/m/Y') ?? 'Pendiente',
                'status' => $order->status->label(),
                'total' => '$ ' . number_format($order->items->sum(fn($i) => $i->quantity * $i->unit_cost), 2),
            ];
        })->toArray();

        $hiddenFields = ['id', 'status_raw'];
        return view('admin.provider-order.index', compact('rowData', 'headers', 'hiddenFields'));
    }

    public function create()
    {
        $formData = (object) [
            'providers' => Provider::orderBy('business_name')->get(),
            'products' => [],
            'currencyOptions' => CurrencyType::forSelect(),
            'statusOptions' => ProviderOrderStatus::forSelect(),
        ];
        return view('admin.provider-order.create', compact('formData'));
    }

    public function store(ProviderOrderWebRequest $request) // <-- Usamos el nuevo Request
    {
        try {
            return DB::transaction(function () use ($request) {
                $provider = Provider::findOrFail($request->provider_id);
                $order = $this->orderService->createOrder($provider);

                $order->update([
                    'order_date' => $request->order_date,
                    'status'     => $request->status
                ]);

                $maxLeadTime = $this->processItems($order, $request);

                $this->updateExpectedDeliveryDate($order, $request, $maxLeadTime);

                return redirect()->route('web.provider-orders.index')
                    ->with('success', 'Orden de compra creada correctamente.');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $order = ProviderOrder::with(['items.providerProduct', 'provider'])->findOrFail($id);

        $currentProviderProducts = ProviderProduct::where('provider_id', $order->provider_id)
            ->with(['product', 'currentPrice'])
            ->get()
            ->map(fn($pp) => [
                'id'       => $pp->id,
                'name'     => $pp->product?->name ?? 'Producto sin nombre',
                'price'    => $pp->currentPrice?->cost_price ?? 0,
                'currency' => $pp->currentPrice?->currency?->value ?? CurrencyType::ARS->value,
            ]);


        // En el método edit()
        $statusOptions = collect(ProviderOrderStatus::forSelect())
            ->except([ProviderOrderStatus::RECEIVED->value, ProviderOrderStatus::PARTIAL->value])
            ->toArray();

        $formData = (object) [
            'order'           => $order,
            'providers'       => Provider::orderBy('business_name')->get(),
            'products'        => $currentProviderProducts,
            'currencyOptions' => CurrencyType::forSelect(),
            'statusOptions' => $statusOptions,
        ];

        return view('admin.provider-order.edit', compact('formData', 'order'));
    }

    public function update(ProviderOrderWebRequest $request, $id) // <-- Usamos el nuevo Request
    {
        try {
            return DB::transaction(function () use ($request, $id) {
                $order = ProviderOrder::findOrFail($id);

                if ($order->status !== ProviderOrderStatus::PENDING) {
                    throw new \Exception("Esta orden ya no se puede editar porque está en estado: " . $order->status->label());
                }

                $order->update([
                    'provider_id' => $request->provider_id,
                    'status'      => $request->status,
                    'order_date'  => $request->order_date
                ]);

                $order->items()->delete();
                $maxLeadTime = $this->processItems($order, $request);

                $this->updateExpectedDeliveryDate($order, $request, $maxLeadTime);

                return redirect()->route('web.provider-orders.index')
                    ->with('success', 'Orden de compra actualizada correctamente.');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Lógica centralizada para determinar la fecha de entrega
     */
    private function updateExpectedDeliveryDate(ProviderOrder $order, Request $request, int $maxLeadTime)
    {
        $deliveryDate = $request->expected_delivery_date;

        // Si es vacío, calculamos basándonos en el lead time máximo encontrado
        if (!$deliveryDate && $maxLeadTime > 0) {
            $deliveryDate = Carbon::parse($request->order_date)->addDays($maxLeadTime);
        }

        $order->update([
            'expected_delivery_date' => $deliveryDate
        ]);
    }

    private function processItems(ProviderOrder $order, $request): int
    {
        $maxLeadTime = 0;
        foreach ($request->items as $index => $item) {
            $providerProduct = ProviderProduct::findOrFail($item['provider_product_id']);

            if ($providerProduct->lead_time_days > $maxLeadTime) {
                $maxLeadTime = $providerProduct->lead_time_days;
            }

            $amount = $request->input("unit_cost_row_{$index}_amount");
            $currency = $request->input("unit_cost_row_{$index}_currency");

            $this->orderService->addItem($order, $providerProduct, $item['quantity'], $amount, $currency);
        }
        return $maxLeadTime;
    }

    public function show($id)
    {
        $order = ProviderOrder::with(['provider', 'branch', 'items.providerProduct.product'])->findOrFail($id);

        $headers = ['Producto', 'SKU Prov.', 'Cantidad', 'Costo Unit.', 'Subtotal'];

        $rowData = $order->items->map(function ($item) {
            // Obtenemos el símbolo (ej: "$" o "U$D")
            $symbol = $item->currency->symbol();

            return [
                'product'   => $item->providerProduct->product->name,
                'sku'       => $item->providerProduct->provider_sku ?? 'N/A',
                'quantity'  => $item->quantity,
                'unit_cost' => $symbol . ' ' . number_format($item->unit_cost, 2, ',', '.'),
                'subtotal'  => $symbol . ' ' . number_format($item->quantity * $item->unit_cost, 2, ',', '.'),
            ];
        })->toArray();

        $hiddenFields = [];

        return view('admin.provider-order.details', compact('order', 'headers', 'rowData', 'hiddenFields'));
    }

    public function send($id)
    {
        try {
            $order = ProviderOrder::findOrFail($id);
            $this->orderService->sendOrder($order);
            return redirect()->route('web.provider-orders.index')->with('success', 'La orden ha sido marcada como ENVIADA.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receive($id) // Cambiamos a $id para buscarlo manualmente o usa Route Model Binding
    {
        try {
            $order = ProviderOrder::findOrFail($id);

            // Llamamos al servicio para procesar el stock y precios
            $this->orderService->receiveOrder($order);

            return redirect()->route('web.provider-orders.index')
                ->with('success', 'Pedido recibido. El stock y los precios han sido actualizados.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al recibir la orden: ' . $e->getMessage());
        }
    }
}
