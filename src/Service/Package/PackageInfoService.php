<?php
declare(strict_types=1);

namespace App\Service\Package;

use App\Service\HttpService;
use App\Service\Package\Market\MarketConfigService;
use App\Service\Package\Market\MarketFetcher;
use Cake\Cache\Cache;
use Cake\Core\Configure;

final class PackageInfoService
{
    private mixed $addonsMarket = null;
    private mixed $themesMarket = null;

    private ?array $addonIndex = null;
    private ?array $themeIndex = null;

    private HttpService $http;
    private MarketConfigService $markets;
    private MarketFetcher $fetcher;

    public function __construct(?HttpService $http = null, ?MarketConfigService $markets = null, ?MarketFetcher $fetcher = null)
    {
        $this->http = $http ?? new HttpService();
        $this->markets = $markets ?? new MarketConfigService();
        $this->fetcher = $fetcher ?? new MarketFetcher($this->http);
    }

    public function cmsCurrentVersion(): string
    {
        $packages = (new PackageManagerFactory())->create();

        return $packages->cmsCurrentVersion();
    }

    public function cmsLatestVersion(): string
    {
        $packages = (new PackageManagerFactory())->create();

        return $packages->cmsLatestVersion() ?: $packages->cmsCurrentVersion();
    }

    public function cmsHasUpdate(): bool
    {
        return version_compare($this->cmsCurrentVersion(), $this->cmsLatestVersion(), '<');
    }

    public function addonInstalledVersion(string $key): ?string
    {
        return $this->installedVersion('addons', $key);
    }

    public function themeInstalledVersion(string $key): ?string
    {
        return $this->installedVersion('themes', $key);
    }

    public function addonLatestVersion(string $slug): ?string
    {
        return $this->latestVersion('addons', $slug);
    }

    public function themeLatestVersion(string $slug): ?string
    {
        return $this->latestVersion('themes', $slug);
    }

    public function addonsLatestVersions(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter(array_map(
            static fn($v) => trim((string)$v),
            $slugs
        ), static fn($v) => $v !== '')));

        $out = [];
        foreach ($slugs as $slug) {
            $v = $this->addonLatestVersion($slug);
            if (is_string($v) && $v !== '') {
                $out[$slug] = $v;
            }
        }

        return $out;
    }

    public function addonHasUpdate(string $keyOrSlug): bool
    {
        return $this->hasUpdate('addons', $keyOrSlug);
    }

    public function themeHasUpdate(string $keyOrSlug): bool
    {
        return $this->hasUpdate('themes', $keyOrSlug);
    }

    public function addonsHaveAnyUpdate(): bool
    {
        return $this->haveAnyUpdate('addons');
    }

    public function themesHaveAnyUpdate(): bool
    {
        return $this->haveAnyUpdate('themes');
    }

    public function addonsMarketEntries(bool $all = false, bool $removeInstalledPlugins = false, array $installedSlugsLower = []): array|false
    {
        return $this->marketEntries('addons', $all, $removeInstalledPlugins, $installedSlugsLower);
    }

    public function themesMarketEntries(bool $all = true, bool $removeInstalledThemes = false, array $installedSlugsLower = []): array|false
    {
        return $this->marketEntries('themes', $all, $removeInstalledThemes, $installedSlugsLower);
    }

    public function addonMarketEntry(string $slug): array|false
    {
        $e = $this->marketEntry('addons', $slug);

        return $e ?? false;
    }

    public function themeMarketEntry(string $slug): array|false
    {
        $e = $this->marketEntry('themes', $slug);

        return $e ?? false;
    }

    private function installedVersion(string $kind, string $key): ?string
    {
        $key = $this->normalizeKey($key);
        if ($key === '') {
            return null;
        }

        $idx = $this->index($kind);

        return $idx['versions'][$key] ?? null;
    }

    private function latestVersion(string $kind, string $slug): ?string
    {
        $entry = $this->marketEntry($kind, $slug);
        if (!is_array($entry)) {
            return null;
        }

        return is_string($entry['version'] ?? null) ? (string)$entry['version'] : null;
    }

    private function hasUpdate(string $kind, string $keyOrSlug): bool
    {
        $installed = $this->installedVersion($kind, $keyOrSlug);
        if ($installed === null) {
            return false;
        }

        $slug = $this->slugFromKeyOrSlug($kind, $keyOrSlug);
        if ($slug === null) {
            return false;
        }

        $latest = $this->latestVersion($kind, $slug);
        if ($latest === null) {
            return false;
        }

        return version_compare($installed, $latest, '<');
    }

    private function haveAnyUpdate(string $kind): bool
    {
        $idx = $this->index($kind);

        foreach ($idx['slugs'] as $slugLower) {
            $installed = $idx['versions'][$slugLower] ?? null;
            if (!is_string($installed) || $installed === '') {
                continue;
            }

            $latest = $this->latestVersion($kind, $slugLower);
            if (!is_string($latest) || $latest === '') {
                continue;
            }

            if (version_compare($installed, $latest, '<')) {
                return true;
            }
        }

        return false;
    }

    private function marketEntries(string $kind, bool $all, bool $removeInstalled, array $installedSlugsLower): array|false
    {
        $list = $this->marketRaw($kind);
        if ($list === false) {
            return false;
        }

        $out = [];
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (!$all && empty($entry['free'])) {
                continue;
            }

            $out[] = $entry;
        }

        if ($removeInstalled) {
            $installedSlugsLower = array_values(array_unique(array_filter(array_map(
                static fn($v) => strtolower(trim((string)$v)),
                $installedSlugsLower
            ), static fn($v) => $v !== '')));

            if ($installedSlugsLower !== []) {
                $out = array_values(array_filter($out, static function ($p) use ($installedSlugsLower) {
                    $slug = strtolower((string)($p['slug'] ?? ''));

                    return $slug !== '' && !in_array($slug, $installedSlugsLower, true);
                }));
            }
        }

        return $out;
    }

    private function marketEntry(string $kind, string $slug): ?array
    {
        $slug = $this->normalizeKey($slug);
        if ($slug === '') {
            return null;
        }

        $list = $this->marketRaw($kind);
        if ($list === false) {
            return null;
        }

        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if ($this->normalizeKey((string)($entry['slug'] ?? '')) === $slug) {
                return $entry;
            }
        }

        return null;
    }

    private function slugFromKeyOrSlug(string $kind, string $keyOrSlug): ?string
    {
        $keyOrSlug = $this->normalizeKey($keyOrSlug);
        if ($keyOrSlug === '') {
            return null;
        }

        $idx = $this->index($kind);

        return $idx['keyToSlug'][$keyOrSlug] ?? null;
    }

    private function index(string $kind): array
    {
        $kind = $this->normalizeKind($kind);

        if ($kind === 'addons') {
            if ($this->addonIndex !== null) {
                return $this->addonIndex;
            }

            $this->addonIndex = $this->buildIndex(
                (string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'),
                (string)Configure::read('Update.addons.manifest', 'manifest.json')
            );

            return $this->addonIndex;
        }

        if ($this->themeIndex !== null) {
            return $this->themeIndex;
        }

        $this->themeIndex = $this->buildIndex(
            (string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes'),
            (string)Configure::read('Update.themes.manifest', 'manifest.json')
        );

        return $this->themeIndex;
    }

    private function buildIndex(string $folder, string $manifestFile): array
    {
        $folder = rtrim($folder, DS);
        if (!is_dir($folder)) {
            return ['versions' => [], 'keyToSlug' => [], 'slugs' => []];
        }

        $versions = [];
        $keyToSlug = [];
        $slugs = [];

        $entries = scandir($folder) ?: [];
        foreach ($entries as $slug) {
            if (!is_string($slug) || $slug === '.' || $slug === '..' || $slug === '.gitkeep') {
                continue;
            }

            $dir = $folder . DS . $slug;
            if (!is_dir($dir)) {
                continue;
            }

            $path = $dir . DS . $manifestFile;
            $m = $this->readLocalManifest($path);
            if ($m === null) {
                continue;
            }

            $mSlug = strtolower(trim((string)($m['slug'] ?? $slug)));
            $author = strtolower(trim((string)($m['author'] ?? '')));
            $version = trim((string)($m['version'] ?? ''));

            if ($mSlug === '' || $version === '') {
                continue;
            }

            $id = $author !== '' ? ($author . '.' . $mSlug) : $mSlug;

            $versions[$mSlug] = $version;
            $keyToSlug[$mSlug] = $mSlug;

            $versions[$id] = $version;
            $keyToSlug[$id] = $mSlug;

            $slugs[$mSlug] = true;
        }

        return [
            'versions' => $versions,
            'keyToSlug' => $keyToSlug,
            'slugs' => array_keys($slugs),
        ];
    }

    private function readLocalManifest(string $path): ?array
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function marketRaw(string $kind): array|false
    {
        $kind = $this->normalizeKind($kind);

        if ($kind === 'addons') {
            return $this->addonsMarketRaw();
        }

        return $this->themesMarketRaw();
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function addonsMarketRaw(): array|false
    {
        if ($this->addonsMarket !== null) {
            return $this->addonsMarket;
        }

        $defs = $this->markets->definitions('addons');
        $cacheKey = 'update_market_addons_' . sha1(json_encode(array_map(static fn($d) => $d->toMeta(), $defs), JSON_UNESCAPED_UNICODE));

        if ($this->markets->cacheEnabled('addons')) {
            $pool = Cache::pool('default');
            $cached = $pool->get($cacheKey);
            if (is_array($cached)) {
                $this->addonsMarket = $cached;

                return $this->addonsMarket;
            }
        }

        $this->addonsMarket = $this->mergeMarkets('addons', $defs);

        if ($this->markets->cacheEnabled('addons')) {
            $ttl = $this->markets->cacheTtlSeconds('addons');
            $pool = Cache::pool('default');
            $pool->set($cacheKey, $this->addonsMarket, $ttl);
        }

        return $this->addonsMarket;
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function themesMarketRaw(): array|false
    {
        if ($this->themesMarket !== null) {
            return $this->themesMarket;
        }

        $defs = $this->markets->definitions('themes');
        $cacheKey = 'update_market_themes_' . sha1(json_encode(array_map(static fn($d) => $d->toMeta(), $defs), JSON_UNESCAPED_UNICODE));

        if ($this->markets->cacheEnabled('themes')) {
            $pool = Cache::pool('default');
            $cached = $pool->get($cacheKey);
            if (is_array($cached)) {
                $this->themesMarket = $cached;

                return $this->themesMarket;
            }
        }

        $this->themesMarket = $this->mergeMarkets('themes', $defs);

        if ($this->markets->cacheEnabled('themes')) {
            $ttl = $this->markets->cacheTtlSeconds('themes');
            $pool = Cache::pool('default');
            $pool->set($cacheKey, $this->themesMarket, $ttl);
        }

        return $this->themesMarket;
    }

    private function mergeMarkets(string $kind, array $defs): array
    {
        $kind = $this->normalizeKind($kind);

        $merged = [];
        $seen = [];

        foreach ($defs as $def) {
            $decoded = $this->fetcher->fetchRaw($def->source);
            if (!is_array($decoded)) {
                continue;
            }

            $meta = $def->toMeta();

            foreach ($decoded as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $slug = strtolower(trim((string)($entry['slug'] ?? '')));
                if ($slug === '' || isset($seen[$slug])) {
                    continue;
                }

                $normalized = $this->normalizeMarketEntry($entry, $kind, $meta);
                if ($normalized === null) {
                    continue;
                }

                $seen[$slug] = true;
                $merged[] = $normalized;
            }
        }

        return $merged;
    }

    private function normalizeMarketEntry(array $entry, string $kind, array $marketMeta): ?array
    {
        $kind = $this->normalizeKind($kind);

        $slug = trim((string)($entry['slug'] ?? ''));
        if ($slug === '') {
            return null;
        }

        $repo = trim((string)($entry['repo'] ?? ''));
        if ($repo === '') {
            $repo = $this->markets->computeRepository($kind, $slug, ['repo' => '', 'market' => $marketMeta]);
        }

        if ($repo === '') {
            return null;
        }

        $manifest = $entry['manifest'] ?? null;
        if (!is_array($manifest)) {
            return null;
        }

        $type = strtolower(trim((string)($manifest['type'] ?? '')));
        if ($kind === 'addons' && $type !== 'addon') {
            return null;
        }
        if ($kind === 'themes' && $type !== 'theme') {
            return null;
        }

        $author = trim((string)($manifest['author'] ?? ''));
        $version = trim((string)($manifest['version'] ?? ''));
        if ($author === '' || $version === '') {
            return null;
        }

        $fetch = is_array($entry['fetch'] ?? null) ? (array)$entry['fetch'] : [];
        $channel = (string)($fetch['channel'] ?? '');
        $ref = (string)($fetch['ref'] ?? '');

        $requirements = is_array($manifest['requirements'] ?? null) ? (array)$manifest['requirements'] : [];

        $out = $entry;
        $out['slug'] = $slug;
        $out['repo'] = $repo;
        $out['author'] = $author;
        $out['version'] = $version;
        $out['id'] = strtolower($author . '.' . $slug);
        $out['requirements'] = $requirements;
        $out['fetch'] = ['channel' => $channel, 'ref' => $ref];
        $out['compatible'] = (bool)($entry['compatible'] ?? false);
        $out['compatible_error'] = is_string($entry['reason'] ?? null) ? (string)$entry['reason'] : null;
        $out['market'] = $marketMeta;

        return $out;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(trim($key));
    }

    private function normalizeKind(string $kind): string
    {
        $kind = strtolower(trim($kind));

        return $kind === 'themes' ? 'themes' : 'addons';
    }
}
