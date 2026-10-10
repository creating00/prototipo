<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Services\Catalogs\CatalogRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class FetchProviderCatalog implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 65;

    public bool $failOnTimeout = true;

    // A real property default also supports jobs serialized before pagination existed.
    public int $batch = 0;

    public function __construct(public int $providerId, public string $token, int $batch = 0)
    {
        $this->batch = $batch;
    }

    public function handle(CatalogRegistry $registry): void
    {
        $catalog = ProviderCatalog::where('provider_id', $this->providerId)->where('token', $this->token)->where('status', 'queued')->first();
        $provider = Provider::find($this->providerId);
        if (! $catalog || ! $provider || ! in_array($catalog->source_url, $provider->catalogUrls(), true)) {
            return;
        }
        if (($catalog->pagination['pages_read'] ?? 0) !== $this->batch) {
            return; // A duplicate/stale continuation must not repeat or overwrite a page.
        }
        ProviderCatalog::whereKey($catalog->id)->where('token', $this->token)->where('status', 'queued')->update(['last_activity_at' => now()]);
        try {
            if (CatalogRegistry::paginatedAdapter($catalog->source_url)) {
                $page = $registry->fetchPage($catalog->pagination['next_url'] ?? $catalog->source_url);
                $this->advance($page);

                return;
            }
            $result = $registry->fetch($catalog->source_url);
            $this->finish(['products' => $result->products, 'status' => $result->status, 'message' => $result->message]);
        } catch (Throwable $e) {
            report($e);
            $this->interrupt('No se pudo consultar o extraer el catálogo. '.mb_substr($e->getMessage(), 0, 500));
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->interrupt('La consulta terminó por error o por superar el tiempo máximo. Podés volver a consultar.');
    }

    private function advance(array $page): void
    {
        DB::transaction(function () use ($page) {
            $provider = Provider::whereKey($this->providerId)->lockForUpdate()->first();
            $catalog = ProviderCatalog::where('provider_id', $this->providerId)->where('token', $this->token)->where('status', 'queued')->lockForUpdate()->first();
            if (! $catalog || ! $provider || ! in_array($catalog->source_url, $provider->catalogUrls(), true)
                || ($catalog->pagination['pages_read'] ?? 0) !== $this->batch) {
                return;
            }
            $state = $catalog->pagination ?? ['pages_read' => 0, 'total' => $page['total'], 'total_pages' => $page['total_pages'], 'from' => $page['from'],
                'mode' => $page['mode'] ?? 'range', 'first_page' => $page['current_page']];
            $numbered = ($state['mode'] ?? 'range') === 'numbered';
            $products = $catalog->products ?? [];
            $seen = [];
            $seenUrls = [];
            foreach ($products as $product) {
                $seen[$this->identity($product)] = true;
                if ($product['url'] ?? null) {
                    $seenUrls[$product['url']] = true;
                }
            }
            $overlap = false;
            foreach ($page['products'] as $product) {
                $overlap = $overlap || isset($seenUrls[$product['url'] ?? '']);
                $key = $this->identity($product);
                if (! isset($seen[$key])) {
                    $products[] = $product;
                    $seen[$key] = true;
                }
            }
            $reason = $page['reason'];
            if ($state['total'] !== $page['total'] || ($page['total_pages'] !== null && $state['total_pages'] !== $page['total_pages'])) {
                $reason = 'El total publicado cambió durante la lectura. Volvé a consultar para obtener un listado consistente.';
            }
            if ($this->batch && ($numbered ? $page['current_page'] !== ($state['current_page'] ?? 0) + 1 : $page['from'] !== ($state['to'] ?? 0) + 1)) {
                $reason = 'La página recibida no continúa el rango anterior; se detuvo la lectura.';
            }
            if ($numbered && $overlap) {
                $reason = 'Se repitieron productos entre páginas; el listado puede haber cambiado durante la lectura.';
            }
            if (count($products) === count($catalog->products ?? []) && $this->batch) {
                $reason = 'La página repitió productos ya obtenidos; se detuvo la lectura.';
            }
            if (count($products) > 20000 || strlen(json_encode($products, JSON_THROW_ON_ERROR)) > 10000000) {
                throw new \RuntimeException('Se alcanzó el límite de productos o de 10 MB por consulta.');
            }
            $state['pages_read']++;
            $state['to'] = $page['to'];
            $state['current_page'] = $page['current_page'];
            $state['next_url'] = $page['next_url'];
            if (($state['pages_read'] >= 400 || ($page['current_page'] ?? 0) >= 400) && $state['next_url']) {
                $reason = 'Se alcanzó el límite de 400 páginas por consulta.';
            }
            $continue = $state['next_url'] && ! $reason;
            $complete = ! $continue && ! $reason && ! $state['next_url'] && ($numbered
                ? ($page['end_verified'] ?? false) && $state['first_page'] === 1 && $state['pages_read'] === $state['total_pages']
                : $state['from'] === 1 && $state['total'] !== null && count($products) === $state['total'] && $state['to'] === $state['total']);
            $message = $state['pages_read'].' páginas leídas; '.count($products).' productos'
                .($state['total'] !== null ? ' de '.$state['total'].' publicados' : '').'. ';
            $message .= $continue ? 'La lectura continúa por tandas en la cola.'
                : ($complete ? 'Se obtuvo el listado publicado de esta URL.' : ($reason ?? 'No se pudo verificar la totalidad del listado.'));
            $message .= ' '.($page['notice'] ?? 'No se consultan fichas individuales. El símbolo $ no permite inferir moneda; las variantes pueden no tener un precio único.');
            $catalog->update(['products' => $products, 'pagination' => $state, 'last_activity_at' => now(),
                'status' => $continue ? 'queued' : ($complete ? 'complete' : 'partial'),
                'message' => $message, 'completed_at' => $continue ? null : now()]);
            if ($continue) {
                DB::afterCommit(function () use ($state) {
                    try {
                        self::dispatch($this->providerId, $this->token, $state['pages_read'])
                            ->onConnection($this->connection)->onQueue($this->queue);
                    } catch (Throwable $exception) {
                        report($exception);
                        (new self($this->providerId, $this->token, $state['pages_read']))
                            ->interrupt('No se pudo enviar la siguiente página a la cola.');
                    }
                });
            }
        });
    }

    private function identity(array $product): string
    {
        // Exact published URL and SKU, never fuzzy name equivalence.
        $key = ($product['url'] ?? null) ? $product['url'].'|'.($product['code'] ?? '') : json_encode($product, JSON_THROW_ON_ERROR);
        $offers = $product['details']['offers'] ?? [];
        if (is_array($offers) && array_is_list($offers) && count($offers) > 1) {
            $key .= '|'.json_encode([$product['price'] ?? null, $product['currency'] ?? null, $product['availability'] ?? null], JSON_THROW_ON_ERROR);
        }

        return hash('sha256', $key);
    }

    private function interrupt(string $message): void
    {
        DB::transaction(function () use ($message) {
            $catalog = ProviderCatalog::where('provider_id', $this->providerId)->where('token', $this->token)->where('status', 'queued')->lockForUpdate()->first();
            if (! $catalog || ($catalog->pagination['pages_read'] ?? 0) !== $this->batch) {
                return;
            }
            $catalog->update(['status' => empty($catalog->products) ? 'failed' : 'partial', 'message' => $message.' Se conservaron los productos obtenidos; el listado puede estar incompleto.', 'completed_at' => now(), 'last_activity_at' => now()]);
        });
    }

    private function finish(array $data): void
    {
        $data['products'] = json_encode($data['products'], JSON_THROW_ON_ERROR);
        ProviderCatalog::where('provider_id', $this->providerId)->where('token', $this->token)->where('status', 'queued')
            ->update($data + ['completed_at' => now()]);
    }
}
