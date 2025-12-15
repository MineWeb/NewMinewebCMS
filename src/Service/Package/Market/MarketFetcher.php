<?php
declare(strict_types=1);

namespace App\Service\Package\Market;

use App\Service\HttpService;

final class MarketFetcher
{
    public function __construct(private readonly HttpService $http)
    {
    }

    public function fetchRaw(string $source): ?array
    {
        $raw = $this->readSource($source);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function readSource(string $source): ?string
    {
        $source = trim($source);
        if ($source === '') {
            return null;
        }

        $parts = parse_url($source);
        $scheme = is_array($parts) && isset($parts['scheme']) ? strtolower((string)$parts['scheme']) : '';

        if ($scheme === 'http' || $scheme === 'https') {
            return $this->http->sendGetRequest($source);
        }

        if ($scheme === 'file') {
            $path = (string)($parts['path'] ?? '');
            if ($path !== '' && is_file($path)) {
                $c = file_get_contents($path);

                return is_string($c) ? $c : null;
            }

            return null;
        }

        if (is_file($source)) {
            $c = file_get_contents($source);

            return is_string($c) ? $c : null;
        }

        $fallback = ROOT . DS . ltrim($source, DS);
        if (is_file($fallback)) {
            $c = file_get_contents($fallback);

            return is_string($c) ? $c : null;
        }

        return null;
    }
}
