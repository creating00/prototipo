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
        ProviderCatalog::where('status', 'queued')->whereRaw('COALESCE(last_activity_at, requested_at) < ?', [now()->subMinutes(2)])
            ->get()->each(function (ProviderCatalog $stalled) {
                // Recheck the heartbeat so a page finishing concurrently cannot be expired.
                ProviderCatalog::whereKey($stalled->id)->where('status', 'queued')
                    ->whereRaw('COALESCE(last_activity_at, requested_at) < ?', [now()->subMinutes(2)])
                    ->update(['status' => empty($stalled->products) ? 'failed' : 'partial', 'message' => 'La cola no registró avance en dos minutos. Verificá el worker y volvé a consultar. Los productos obtenidos se conservan como listado parcial.', 'completed_at' => now()]);
            });
        $providers = Provider::orderBy('business_name')->get()
            ->filter(fn (Provider $provider) => collect($provider->catalogUrls())->contains(fn (string $url) => trim($url) !== ''))
            ->values();
        $catalogs = ProviderCatalog::whereIn('provider_id', $providers->modelKeys())->get();
        $selectedProvider = $providers->firstWhere('id', (int) $request->query('provider')) ?? $providers->first();
        $sourceUrls = $selectedProvider?->catalogUrls() ?? [];
        $requestedUrl = $request->query('source_url');
        $selectedSourceUrl = in_array($requestedUrl, $sourceUrls, true) ? $requestedUrl : ($sourceUrls[0] ?? null);
        $sourceCatalogs = $catalogs->where('provider_id', $selectedProvider?->id)->keyBy('source_url');
        $catalog = $selectedSourceUrl ? $sourceCatalogs->get($selectedSourceUrl) : null;
        $products = collect($catalog?->products ?? []);
        $page = max(1, (int) $request->query('page', 1));
        $entries = new \Illuminate\Pagination\LengthAwarePaginator($products->slice(($page - 1) * 5, 5), $products->count(), 5, $page, ['path' => $request->url(), 'query' => $request->query(), 'fragment' => 'catalog-published-products']);
        $processed = $catalog ? ProviderCatalogSnapshot::where('query_token', $catalog->token)->first() : null;
        $history = ProviderCatalogSnapshot::orderByDesc('processed_at')->orderByDesc('id')
            ->paginate(10, ['*'], 'history_page')->withQueryString();
        $processedProductCount = ProviderCatalogItem::count();
        $comparisonProviders = ProviderCatalogSnapshot::whereHas('products')
            ->select('provider_id', DB::raw('MAX(provider_name) as provider_name'))
            ->groupBy('provider_id')->orderBy('provider_name')->get();
        $oldEntries = collect($request->old('entries', []))->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id))->take(8);
        $selectedItems = ProviderCatalogItem::with('snapshot')->whereIn('id', $oldEntries)->get();

        return view('admin.provider.catalogs', compact('providers', 'catalogs', 'selectedProvider', 'sourceUrls', 'selectedSourceUrl', 'sourceCatalogs', 'catalog', 'entries', 'processed', 'history', 'processedProductCount', 'selectedItems', 'comparisonProviders'));
    }

    public function refresh(Request $request, Provider $provider)
    {
        $this->authorizeCatalog();
        $this->authorize('update', $provider);
        if ($provider->catalogUrls() === []) {
            throw ValidationException::withMessages(['catalog_url' => 'Configurá primero la URL del catálogo del proveedor.']);
        }
        if (in_array(config('queue.default'), ['sync', 'null'], true)) {
            return back()->with('error', 'Configurá una cola asíncrona (por ejemplo database) y ejecutá su worker antes de consultar.');
        }
        $validated = $request->validate(['source_url' => ['nullable', 'string', 'max:2048', new \App\Rules\CatalogUrl]]);
        $sourceUrl = $validated['source_url'] ?? $provider->catalogUrls()[0];
        $catalog = DB::transaction(function () use ($provider, $sourceUrl) {
            $currentProvider = Provider::whereKey($provider->id)->lockForUpdate()->firstOrFail();
            if (! in_array($sourceUrl, $currentProvider->catalogUrls(), true)) {
                throw ValidationException::withMessages(['source_url' => 'La URL no está configurada para este proveedor.']);
            }
            $sourceKey = hash('sha256', $sourceUrl);
            $current = ProviderCatalog::where('provider_id', $provider->id)->where('source_key', $sourceKey)->first();
            if ($current?->status === 'queued' && ($current->last_activity_at ?? $current->requested_at)->gt(now()->subMinutes(2))) {
                throw ValidationException::withMessages(['catalog' => 'Ya hay una consulta en curso para este proveedor.']);
            }

            return ProviderCatalog::updateOrCreate(['provider_id' => $provider->id, 'source_key' => $sourceKey], [
                'token' => (string) Str::uuid(), 'status' => 'queued', 'source_url' => $sourceUrl,
                'products' => [], 'message' => 'Consulta en cola. El historial procesado se conserva.',
                'requested_at' => now(), 'completed_at' => null, 'pagination' => null, 'last_activity_at' => null,
            ]);
        });
        try {
            FetchProviderCatalog::dispatch($provider->id, $catalog->token);
        } catch (Throwable $e) {
            report($e);
            $catalog->update(['status' => 'failed', 'message' => 'No se pudo enviar la consulta a la cola.', 'completed_at' => now()]);
        }

        return redirect()->route('web.provider-catalogs.index', ['provider' => $provider->id, 'source_url' => $sourceUrl]);
    }

    public function clear(Request $request, CatalogProcessingService $service)
    {
        $this->authorizeCatalog();
        $this->authorize('clearCatalog', Provider::class);
        abort_if(session('active_branch_id') === 'all', 403);
        $this->authorize('providers.delete');
        $request->validate(['confirm_clear' => ['required', 'accepted']]);
        $service->clear();

        return redirect()->route('web.provider-catalogs.index')
            ->with('success', 'Se eliminaron las vistas previas, los productos y el historial de todos los catálogos web. Las URLs de proveedores y el inventario se conservan.');
    }

    public function process(Request $request, Provider $provider, CatalogProcessingService $service)
    {
        $this->authorizeCatalog();
        $this->authorize('update', $provider);
        $validated = $request->validate(['token' => ['required', 'uuid']]);
        $snapshot = $service->process($provider->id, $validated['token'], $request->user()->id);

        return redirect()->route('web.provider-catalogs.index', ['provider' => $provider->id, 'source_url' => $snapshot->source_url])
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
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'provider_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = trim($validated['q'] ?? '');
        $items = ProviderCatalogItem::with('snapshot')
            ->join('provider_catalog_snapshots', 'provider_catalog_snapshots.id', '=', 'provider_catalog_items.snapshot_id')
            ->when($validated['provider_id'] ?? null, fn ($builder, $providerId) => $builder->where('provider_catalog_snapshots.provider_id', $providerId))
            ->when($query !== '', fn ($builder) => $builder->where(function ($builder) use ($query) {
                $builder->where('provider_catalog_items.name', 'like', '%'.$query.'%')
                    ->orWhere('provider_catalog_items.code', 'like', '%'.$query.'%')
                    ->orWhere('provider_catalog_snapshots.provider_name', 'like', '%'.$query.'%')
                    ->orWhere('provider_catalog_snapshots.source_url', 'like', '%'.$query.'%');
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

    public function suggestions(Request $request, \App\Services\Catalogs\CatalogComparisonSearch $search)
    {
        $this->authorizeCatalog();
        $validated = $request->validate([
            'entry_id' => ['required', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $item = ProviderCatalogItem::with('snapshot')->findOrFail($validated['entry_id']);

        return response()->json($search->candidates($item, $validated['q'] ?? ''));
    }
}
