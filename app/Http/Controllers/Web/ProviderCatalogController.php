<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\FetchProviderCatalog;
use App\Models\Provider;
use App\Models\ProviderCatalog;
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

        return view('admin.provider.catalogs', compact('providers', 'catalogs', 'selectedProvider', 'catalog', 'entries'));
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
                'products' => [], 'message' => 'Consulta en cola. Se reemplazó el resultado anterior.',
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

    public function compare(Request $request)
    {
        $this->authorizeCatalog();
        if (is_array($request->input('entries'))) {
            $request->merge(['entries' => array_values(array_filter($request->input('entries'), fn ($entry) => $entry !== null && $entry !== ''))]);
        }
        $validated = $request->validate([
            'entries' => ['required', 'array', 'min:2', 'max:8'],
            'entries.*' => ['required', 'string', 'distinct', 'max:100'],
            'equivalence_reviewed' => ['accepted'],
        ], ['equivalence_reviewed.accepted' => 'Confirmá que revisaste modelo, variante y presentación de los productos seleccionados.']);
        $rows = [];
        foreach ($validated['entries'] as $reference) {
            $parts = explode(':', $reference);
            if (count($parts) !== 3 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[2])) {
                throw ValidationException::withMessages(['entries' => 'Selección inválida.']);
            }
            $catalog = ProviderCatalog::with('provider')->find($parts[0]);
            if (! $catalog?->provider || ! hash_equals($catalog->token, $parts[1])
                || ! in_array($catalog->status, ['partial', 'complete'], true)
                || $catalog->provider->catalog_url !== $catalog->source_url
                || ! isset($catalog->products[(int) $parts[2]])) {
                throw ValidationException::withMessages(['entries' => 'La consulta seleccionada cambió o ya no está disponible. Volvé a seleccionar los productos.']);
            }
            $rows[] = ['provider' => $catalog->provider, 'queried_at' => $catalog->completed_at, 'product' => $catalog->products[(int) $parts[2]]];
        }
        if (collect($rows)->pluck('provider.id')->unique()->count() < 2) {
            throw ValidationException::withMessages(['entries' => 'Elegí productos de al menos dos proveedores diferentes.']);
        }
        // No currency conversion, name similarity matching, or automatic equivalence.
        $groups = collect($rows)->groupBy(fn ($row) => $row['product']['currency'] ?? 'Moneda no informada');

        return view('admin.provider.catalog-comparison', compact('groups'));
    }
}
