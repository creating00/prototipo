<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Models\ProviderCatalog;
use App\Services\Catalogs\CatalogRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class FetchProviderCatalog implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 65;

    public bool $failOnTimeout = true;

    public function __construct(public int $providerId, public string $token) {}

    public function handle(CatalogRegistry $registry): void
    {
        $catalog = ProviderCatalog::where('provider_id', $this->providerId)->where('token', $this->token)->where('status', 'queued')->first();
        $provider = Provider::find($this->providerId);
        if (! $catalog || ! $provider || $provider->catalog_url !== $catalog->source_url) {
            return;
        }
        try {
            $result = $registry->fetch($catalog->source_url);
            $this->finish(['products' => $result->products, 'status' => $result->status, 'message' => $result->message]);
        } catch (Throwable $e) {
            report($e);
            $this->finish(['products' => [], 'status' => 'failed', 'message' => 'No se pudo consultar o extraer el catálogo. '.mb_substr($e->getMessage(), 0, 500)]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->finish(['products' => [], 'status' => 'failed', 'message' => 'La consulta terminó por error o por superar el tiempo máximo. Podés volver a consultar.']);
    }

    private function finish(array $data): void
    {
        $data['products'] = json_encode($data['products'], JSON_THROW_ON_ERROR);
        ProviderCatalog::where('provider_id', $this->providerId)->where('token', $this->token)->where('status', 'queued')
            ->update($data + ['completed_at' => now()]);
    }
}
