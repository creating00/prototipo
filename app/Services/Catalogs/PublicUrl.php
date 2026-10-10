<?php

namespace App\Services\Catalogs;

use InvalidArgumentException;

class PublicUrl
{
    public function parts(string $url): array
    {
        $parts = parse_url($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! is_array($parts)
            || ! in_array($parts['scheme'] ?? '', ['https', 'http'], true)
            || isset($parts['user']) || isset($parts['pass'])
            || preg_match('/[\x00-\x20\\\\]/', $url)) {
            throw new InvalidArgumentException('La URL debe ser HTTP(S), sin credenciales ni caracteres de control.');
        }
        $host = strtolower($parts['host'] ?? '');
        $port = $parts['port'] ?? ($parts['scheme'] === 'https' ? 443 : 80);
        if (! str_contains($host, '.') || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)
            || ! preg_match('/^[a-z0-9.-]+$/D', $host)
            || preg_match('/(?:^|\.)(?:localhost|local|internal|test|invalid)$/D', $host)
            || $port !== ($parts['scheme'] === 'https' ? 443 : 80)) {
            throw new InvalidArgumentException('Usá un dominio público y el puerto estándar HTTP(S).');
        }

        return $parts + ['port' => $port];
    }

    public function addresses(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = [];
        foreach ($records ?: [] as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if ($ip !== null) {
                if (! $this->isPublicAddress($ip)) {
                    throw new InvalidArgumentException('El catálogo resuelve a una dirección interna o reservada.');
                }
                $addresses[] = $ip;
            }
        }
        if ($addresses === []) {
            throw new InvalidArgumentException('No se pudo resolver el dominio del catálogo.');
        }

        return array_unique($addresses);
    }

    public function isPublicAddress(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $number = (int) sprintf('%u', ip2long($ip));
            foreach (['0.0.0.0/8', '100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/3'] as $range) {
                [$network, $bits] = explode('/', $range);
                $mask = (0xFFFFFFFF << (32 - (int) $bits)) & 0xFFFFFFFF;
                if (($number & $mask) === (ip2long($network) & $mask)) {
                    return false;
                }
            }

            return true;
        }
        $binary = inet_pton($ip);

        // Only global unicast; reject mapped/NAT64, ULA, multicast and special 2001 ranges.
        return (ord($binary[0]) & 0xE0) === 0x20
            && ! str_starts_with(strtolower($ip), '2001:')
            && ! str_starts_with(strtolower($ip), '2002:');
    }

    public static function displayLink(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        try {
            (new self)->parts($url);

            return $url;
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
