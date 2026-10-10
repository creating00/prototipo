<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\FetchProviderCatalog;
use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Models\ProviderCatalogItem;
use App\Models\ProviderCatalogSnapshot;
use App\Services\Catalogs\CatalogProcessingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProviderCatalogController extends Controller
{
    use AuthorizesRequests;

    private function authorizeCatalog(): void
    {
        $this->authorize('viewAny', Provider::class);
        $this->authorize('product_provider_prices.view');
    }

    public function index(Request $request)
    {
        $this->authorizeCatalog();
        // A missing/stopped worker must not leave a permanently pending screen.
        ProviderCatalog::where('status', 'queued')->where('requested_at', '<', now()->subMinutes(2))
            ->update(['status' => 'failed', 'message' => 'La cola no finalizó la consulta en dos minutos. Verificá el worker y volvé a consultar.', 'completed_at' => now()]);
        $providers = Provider::orderBy('business_name')->get();
        $catalogs = ProviderCatalog::whereIn('provider_id', $providers->modelKeys())->get()->keyBy('provider_id');
        $selectedProvider = $providers->firstWhere('id', (int) $request->query('provider')) ?? $providers->first();
        $catalog = $selectedProvider ? $catalogs->get($selectedProvider->id) : null;
        $products = collect($catalog?->products ?? []);
        $page = max(1, (int) $request->query('page', 1));
        $entries = new \Illuminate\Pagination\LengthAwarePaginator($products->slice(($page - 1) * 30, 30), $products->count(), 30, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $processed = $catalog ? ProviderCatalogSnapshot::where('query_token', $catalog->token)->first() : null;
        $history = ProviderCatalogSnapshot::orderByDesc('processed_at')->orderByDesc('id')
            ->paginate(10, ['*'], 'history_page')->withQueryString();
        $processedProductCount = ProviderCatalogItem::count();
        $oldEntries = collect($request->old('entries', []))->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))->take(8);
        $selectedItems = ProviderCatalogItem::with('snapshot')->whereIn('id', $oldEntries)->get();

        return view('admin.provider.catalogs', compact('providers', 'catalogs', 'selectedProvider', 'catalog', 'entries', 'processed', 'history', 'processedProductCount', 'selectedItems'));
    }

    public function refresh(Provider $provider)
    {
        $this->authorizeCatalog();
        $this->authorize('update', $provider);
        if (! $provider->catalog_url) {
            throw ValidationException::withMessages(['catalog_url' => 'Configurá primero la URL del catálogo del proveedor.']);
        }
        if (in_array(config('queue.default'), ['sync', 'null'], true)) {
            return back()->with('error', 'Configurá una cola asíncrona (por ejemplo database) y ejecutá su worker antes de consultar.');
        }
        $catalog = DB::transaction(function () use ($provider) {
            Provider::whereKey($provider->id)->lockForUpdate()->firstOrFail();
            $current = ProviderCatalog::where('provider_id', $provider->id)->first();
            if ($current?->status === 'queued' && $current->requested_at->gt(now()->subMinutes(2))) {
                throw ValidationException::withMessages(['catalog' => 'Ya hay una consulta en curso para este proveedor.']);
            }

            return ProviderCatalog::updateOrCreate(['provider_id' => $provider->id], [
                'token' => (string) Str::uuid(), 'status' => 'queued', 'source_url' => $provider->catalog_url,
                'products' => [], 'message' => 'Consulta en cola. El historial procesado se conserva.',
                'requested_at' => now(), 'completed_at' => null,
            ]);
        });
        try {
            FetchProviderCatalog::dispatch($provider->id, $catalog->token);
        } catch (Throwable $e) {
            report($e);
            $catalog->update(['status' => 'failed', 'message' => 'No se pudo enviar la consulta a la cola.', 'completed_at' => now()]);
        }

        return redirect()->route('web.provider-catalogs.index', ['provider' => $provider->id]);
    }

    public function process(Request $request, Provider $provider, CatalogProcessingService $service)
    {
        $this->authorizeCatalog();
        $this->authorize('update', $provider);
        $validated = $request->validate(['token' => ['required', 'uuid']]);
        $snapshot = $service->process($provider->id, $validated['token'], $request->user()->id);

        return redirect()->route('web.provider-catalogs.index', ['provider' => $provider->id])
            ->with('success', 'Consulta procesada: '.$snapshot->product_count.' productos guardados en el historial y disponibles en los combos de comparación.');
    }

    public function history(Request $request, ProviderCatalogSnapshot $snapshot)
    {
        $this->authorizeCatalog();
        $entries = $snapshot->products()->orderBy('position')->paginate(30)->withQueryString();

        return view('admin.provider.catalog-history', compact('snapshot', 'entries'));
    }

    public function products(Request $request)
    {
        $this->authorizeCatalog();
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $query = trim($validated['q'] ?? '');
        $items = ProviderCatalogItem::with('snapshot')
            ->join('provider_catalog_snapshots', 'provider_catalog_snapshots.id', '=', 'provider_catalog_items.snapshot_id')
            ->when($query !== '', fn ($builder) => $builder->where(function ($builder) use ($query) {
                $builder->where('provider_catalog_items.name', 'like', '%'.$query.'%')
                    ->orWhere('provider_catalog_items.code', 'like', '%'.$query.'%')
                    ->orWhere('provider_catalog_snapshots.provider_name', 'like', '%'.$query.'%');
            }))
            ->orderBy('provider_catalog_snapshots.provider_name')
            ->orderBy('provider_catalog_snapshots.provider_id')
            ->orderByDesc('provider_catalog_snapshots.queried_at')
            ->orderByDesc('provider_catalog_snapshots.id')
            ->orderBy('provider_catalog_items.position')
            ->paginate(50, ['provider_catalog_items.*'])->withQueryString();
        $options = $items->getCollection()->map(fn ($item) => $item->comparisonOption());
        $optgroups = $options->unique('optgroup')->map(fn ($option) => ['value' => $option['optgroup'], 'label' => $option['provider']])->values();

        return response()->json(['options' => $options, 'optgroups' => $optgroups, 'next_page_url' => $items->nextPageUrl(), 'total' => $items->total()]);
    }

    public function compare(Request $request)
    {
        $this->authorizeCatalog();
        if (is_array($request->input('entries'))) {
            $request->merge(['entries' => array_values(array_filter($request->input('entries'), fn ($entry) => $entry !== null && $entry !== ''))]);
        }
        $validated = $request->validate([
            'entries' => ['required', 'array', 'min:2', 'max:8'],
            'entries.*' => ['required', 'string', 'regex:/^[1-9][0-9]*$/D', 'distinct', 'max:20'],
            'equivalence_reviewed' => ['accepted'],
        ], ['equivalence_reviewed.accepted' => 'Confirmá que revisaste modelo, variante y presentación de los productos seleccionados.']);
        $rows = [];
        foreach ($validated['entries'] as $reference) {
            $item = ProviderCatalogItem::with('snapshot')->find($reference);
            if (! $item) {
                throw ValidationException::withMessages(['entries' => 'El producto procesado seleccionado no está disponible.']);
            }
            $rows[] = ['provider_name' => $item->snapshot->provider_name, 'provider_id' => $item->snapshot->provider_id,
                'queried_at' => $item->snapshot->queried_at, 'processed_at' => $item->snapshot->processed_at,
                'snapshot_id' => $item->snapshot_id, 'product' => $item->data];
        }
        if (collect($rows)->pluck('provider_id')->unique()->count() < 2) {
            throw ValidationException::withMessages(['entries' => 'Elegí productos de al menos dos proveedores diferentes.']);
        }
        // No currency conversion, name similarity matching, or automatic equivalence.
        $groups = collect($rows)->groupBy(fn ($row) => $row['product']['currency'] ?? 'Moneda no informada');

        return view('admin.provider.catalog-comparison', compact('groups'));
    }
}
