<?php

namespace App\Services\Catalogs;

use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class CatalogHttpClient
{
    private const MAX_BYTES = 2_000_000;

    private ?float $deadline = null;

    public function __construct(private PublicUrl $urls) {}

    /** Every redirect is validated and DNS is pinned to prevent rebinding. No proxy or cookies. */
    public function get(string $url, array $allowedHosts): array
    {
        if (! extension_loaded('curl')) {
            throw new RuntimeException('La consulta segura requiere la extensión cURL.');
        }
        for ($redirect = 0; $redirect <= 3; $redirect++) {
            $this->deadline ??= microtime(true) + 50;
            $remaining = $this->deadline - microtime(true);
            if ($remaining < 1) {
                throw new RuntimeException('La consulta alcanzó el límite total de 50 segundos.');
            }
            $parts = $this->urls->parts($url);
            $host = strtolower($parts['host']);
            $allowed = in_array($host, $allowedHosts, true)
                || (in_array('*.googleusercontent.com', $allowedHosts, true) && str_ends_with($host, '.googleusercontent.com'));
            if (! $allowed) {
                throw new RuntimeException('El catálogo redirigió a un dominio no autorizado.');
            }
            $ips = $this->urls->addresses($host);
            $ip = reset($ips);
            $address = str_contains($ip, ':') ? "[{$ip}]" : $ip;
            $stream = \GuzzleHttp\Psr7\Utils::streamFor(fopen('php://temp/maxmemory:2097152', 'w+'));
            $written = 0;
            $sink = \GuzzleHttp\Psr7\FnStream::decorate($stream, [
                'write' => function (string $bytes) use ($stream, &$written): int {
                    $written += strlen($bytes);
                    if ($written > self::MAX_BYTES) {
                        throw new RuntimeException('El contenido descomprimido supera el límite de 2 MB.');
                    }

                    return $stream->write($bytes);
                },
            ]);
            $response = Http::withoutRedirecting()->connectTimeout(3)->timeout(min(8, $remaining))
                ->withUserAgent('ProviderCatalog/1.0 (on-demand public catalog)')
                ->withOptions([
                    'proxy' => '',
                    'sink' => $sink,
                    'curl' => [CURLOPT_RESOLVE => ["{$host}:{$parts['port']}:{$address}"], CURLOPT_PROXY => '', CURLOPT_NOPROXY => '*'],
                    'on_headers' => function (ResponseInterface $response) {
                        if ((int) $response->getHeaderLine('Content-Length') > self::MAX_BYTES) {
                            throw new RuntimeException('El catálogo supera el límite de 2 MB por respuesta.');
                        }
                    },
                    'progress' => function ($total, $downloaded) {
                        if ($downloaded > self::MAX_BYTES) {
                            throw new RuntimeException('El catálogo supera el límite de descarga.');
                        }
                    },
                ])->get($url);
            if ($response->redirect()) {
                $location = $response->header('Location');
                if (! $location) {
                    throw new RuntimeException('Redirección sin destino.');
                }
                $url = (string) \GuzzleHttp\Psr7\UriResolver::resolve(new \GuzzleHttp\Psr7\Uri($url), new \GuzzleHttp\Psr7\Uri($location));

                continue;
            }
            if (! $response->successful()) {
                throw new RuntimeException('No se pudo acceder al catálogo (HTTP '.$response->status().'). No se intenta evadir restricciones.');
            }
            $body = $response->body();
            if (strlen($body) > self::MAX_BYTES) {
                throw new RuntimeException('El contenido del catálogo supera 2 MB.');
            }

            return ['body' => $body, 'url' => $url, 'total_pages' => $response->header('X-WP-TotalPages')];
        }
        throw new RuntimeException('El catálogo excedió el límite de redirecciones.');
    }
}
