<?php
declare(strict_types=1);

namespace App\Service\Package\Market;

use Cake\Core\Configure;

final class MarketConfigService
{
    public function definitions(string $kind): array
    {
        $kind = $this->normalizeKind($kind);

        $defaults = new MarketDefinition(
            source: 'internal://defaults',
            repoPattern: $this->defaultRepoPattern($kind),
            channel: $this->defaultChannel($kind),
            branch: $this->defaultBranch($kind),
        );

        $defs = [];

        $markets = Configure::read('Update.' . $kind . '.markets');
        if (is_array($markets)) {
            foreach ($markets as $m) {
                if (!is_array($m)) {
                    continue;
                }
                $def = MarketDefinition::fromArray($m, $defaults);
                if ($def) {
                    $defs[] = $def;
                }
            }

            return $defs;
        }

        $legacy = Configure::read('Update.' . $kind . '.market');
        if (is_string($legacy)) {
            $legacy = [$legacy];
        }

        if (is_array($legacy)) {
            foreach ($legacy as $src) {
                $src = trim((string)$src);
                if ($src === '') {
                    continue;
                }
                $def = MarketDefinition::fromArray(['source' => $src], $defaults);
                if ($def) {
                    $defs[] = $def;
                }
            }
        }

        return $defs;
    }

    public function cacheEnabled(string $kind): bool
    {
        $kind = $this->normalizeKind($kind);

        $v = Configure::read('Update.' . $kind . '.cacheEnabled');
        if (is_bool($v)) {
            return $v;
        }

        $v = Configure::read('Update.' . $kind . '.cache.enabled');
        if (is_bool($v)) {
            return $v;
        }

        $v = Configure::read('Update.' . $kind . '.cache_enabled');
        if (is_bool($v)) {
            return $v;
        }

        return true;
    }

    public function cacheTtlSeconds(string $kind): int
    {
        $kind = $this->normalizeKind($kind);

        $raw = Configure::read('Update.' . $kind . '.cacheTtl');
        if (!is_string($raw) && !is_int($raw)) {
            $raw = Configure::read('Update.' . $kind . '.cache.ttl');
        }
        if (!is_string($raw) && !is_int($raw)) {
            $raw = Configure::read('Update.' . $kind . '.cache_ttl');
        }

        if (is_int($raw)) {
            return max(0, $raw);
        }

        $raw = trim((string)$raw);
        if ($raw === '') {
            return 600;
        }

        if (ctype_digit($raw)) {
            return max(0, (int)$raw);
        }

        $now = time();
        $ts = strtotime($raw, $now);
        if ($ts === false) {
            return 600;
        }

        return max(0, $ts - $now);
    }

    public function defaultRepoPattern(string $kind): string
    {
        $kind = $this->normalizeKind($kind);

        $p = trim((string)Configure::read('Update.' . $kind . '.repoPattern', ''));

        return $p !== '' ? $p : ($kind === 'themes' ? 'MineWeb/Theme-%s' : 'MineWeb/Plugin-%s');
    }

    public function defaultChannel(string $kind): string
    {
        $kind = $this->normalizeKind($kind);

        $c = trim((string)Configure::read('Update.' . $kind . '.channel', 'release'));

        return ($c === 'release' || $c === 'branch') ? $c : 'release';
    }

    public function defaultBranch(string $kind): string
    {
        $kind = $this->normalizeKind($kind);

        $b = trim((string)Configure::read('Update.' . $kind . '.branch', '2.X'));

        return $b !== '' ? $b : '2.X';
    }

    public function computeRepository(string $kind, string $slug, array $marketEntry): string
    {
        $kind = $this->normalizeKind($kind);
        $slug = trim($slug);

        $repo = trim((string)($marketEntry['repo'] ?? ''));
        if ($repo !== '') {
            return $repo;
        }

        $meta = is_array($marketEntry['market'] ?? null) ? (array)$marketEntry['market'] : [];
        $pattern = trim((string)($meta['repoPattern'] ?? ''));
        if ($pattern === '') {
            $pattern = $this->defaultRepoPattern($kind);
        }

        return sprintf($pattern, $slug);
    }

    public function marketDefaults(string $kind, array $marketEntry): array
    {
        $kind = $this->normalizeKind($kind);

        $meta = is_array($marketEntry['market'] ?? null) ? (array)$marketEntry['market'] : [];

        $channel = trim((string)($meta['channel'] ?? ''));
        if ($channel !== 'release' && $channel !== 'branch') {
            $channel = $this->defaultChannel($kind);
        }

        $branch = trim((string)($meta['branch'] ?? ''));
        if ($branch === '') {
            $branch = $this->defaultBranch($kind);
        }

        return [$channel, $branch];
    }

    private function normalizeKind(string $kind): string
    {
        $kind = strtolower(trim($kind));

        return $kind === 'themes' ? 'themes' : 'addons';
    }
}
