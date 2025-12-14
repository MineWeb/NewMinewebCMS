<?php
declare(strict_types=1);

namespace App\Service\Package;

use App\Service\HttpService;
use Cake\Cache\Cache;
use Cake\Core\Configure;

final class PackageInfoService
{
    private HttpService $http;

    private mixed $addonsMarket = null;
    private mixed $themesMarket = null;

    private ?array $addonIndex = null;
    private ?array $themeIndex = null;

    public function __construct(?HttpService $http = null)
    {
        $this->http = $http ?? new HttpService();
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
        $key = $this->normalizeKey($key);
        if ($key === '') {
            return null;
        }

        $idx = $this->addonIndex();

        return $idx['versions'][$key] ?? null;
    }

    public function themeInstalledVersion(string $key): ?string
    {
        $key = $this->normalizeKey($key);
        if ($key === '') {
            return null;
        }

        $idx = $this->themeIndex();

        return $idx['versions'][$key] ?? null;
    }

    public function addonLatestVersion(string $slug): ?string
    {
        $entry = $this->addonMarketEntry($slug);
        if (!is_array($entry)) {
            return null;
        }

        return is_string($entry['version'] ?? null) ? (string)$entry['version'] : null;
    }

    public function themeLatestVersion(string $slug): ?string
    {
        $entry = $this->themeMarketEntry($slug);
        if (!is_array($entry)) {
            return null;
        }

        return is_string($entry['version'] ?? null) ? (string)$entry['version'] : null;
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
        $installed = $this->addonInstalledVersion($keyOrSlug);
        if ($installed === null) {
            return false;
        }

        $slug = $this->addonSlugFromKeyOrSlug($keyOrSlug);
        if ($slug === null) {
            return false;
        }

        $latest = $this->addonLatestVersion($slug);
        if ($latest === null) {
            return false;
        }

        return version_compare($installed, $latest, '<');
    }

    public function themeHasUpdate(string $keyOrSlug): bool
    {
        $installed = $this->themeInstalledVersion($keyOrSlug);
        if ($installed === null) {
            return false;
        }

        $slug = $this->themeSlugFromKeyOrSlug($keyOrSlug);
        if ($slug === null) {
            return false;
        }

        $latest = $this->themeLatestVersion($slug);
        if ($latest === null) {
            return false;
        }

        return version_compare($installed, $latest, '<');
    }

    public function addonsHaveAnyUpdate(): bool
    {
        $idx = $this->addonIndex();

        foreach ($idx['slugs'] as $slugLower) {
            $installed = $idx['versions'][$slugLower] ?? null;
            if (!is_string($installed) || $installed === '') {
                continue;
            }

            $latest = $this->addonLatestVersion($slugLower);
            if (!is_string($latest) || $latest === '') {
                continue;
            }

            if (version_compare($installed, $latest, '<')) {
                return true;
            }
        }

        return false;
    }

    public function themesHaveAnyUpdate(): bool
    {
        $idx = $this->themeIndex();

        foreach ($idx['slugs'] as $slugLower) {
            $installed = $idx['versions'][$slugLower] ?? null;
            if (!is_string($installed) || $installed === '') {
                continue;
            }

            $latest = $this->themeLatestVersion($slugLower);
            if (!is_string($latest) || $latest === '') {
                continue;
            }

            if (version_compare($installed, $latest, '<')) {
                return true;
            }
        }

        return false;
    }

    public function addonsMarketEntries(bool $all = false, bool $removeInstalledPlugins = false, array $installedSlugsLower = []): array|false
    {
        $list = $this->addonsMarketRaw();
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

            $normalized = $this->normalizeMarketEntry($entry, 'addons');
            if ($normalized === null) {
                continue;
            }

            $out[] = $normalized;
        }

        if ($removeInstalledPlugins) {
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

    public function themesMarketEntries(bool $all = true, bool $removeInstalledThemes = false, array $installedSlugsLower = []): array|false
    {
        $list = $this->themesMarketRaw();
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

            $normalized = $this->normalizeMarketEntry($entry, 'themes');
            if ($normalized === null) {
                continue;
            }

            $out[] = $normalized;
        }

        if ($removeInstalledThemes) {
            $installedSlugsLower = array_values(array_unique(array_filter(array_map(
                static fn($v) => strtolower(trim((string)$v)),
                $installedSlugsLower
            ), static fn($v) => $v !== '')));

            if ($installedSlugsLower !== []) {
                $out = array_values(array_filter($out, static function ($t) use ($installedSlugsLower) {
                    $slug = strtolower((string)($t['slug'] ?? ''));

                    return $slug !== '' && !in_array($slug, $installedSlugsLower, true);
                }));
            }
        }

        return $out;
    }

    public function addonMarketEntry(string $slug): array|false
    {
        $slug = $this->normalizeKey($slug);
        if ($slug === '') {
            return false;
        }

        $list = $this->addonsMarketRaw();
        if ($list === false) {
            return false;
        }

        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if ($this->normalizeKey((string)($entry['slug'] ?? '')) === $slug) {
                $normalized = $this->normalizeMarketEntry($entry, 'addons');

                return $normalized ?? false;
            }
        }

        return false;
    }

    public function themeMarketEntry(string $slug): array|false
    {
        $slug = $this->normalizeKey($slug);
        if ($slug === '') {
            return false;
        }

        $list = $this->themesMarketRaw();
        if ($list === false) {
            return false;
        }

        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if ($this->normalizeKey((string)($entry['slug'] ?? '')) === $slug) {
                $normalized = $this->normalizeMarketEntry($entry, 'themes');

                return $normalized ?? false;
            }
        }

        return false;
    }

    private function normalizeMarketEntry(array $entry, string $kind): ?array
    {
        $kind = $kind === 'themes' ? 'themes' : 'addons';

        $slug = trim((string)($entry['slug'] ?? ''));
        $repo = trim((string)($entry['repo'] ?? ''));

        if ($slug === '' || $repo === '') {
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

        return $out;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(trim($key));
    }

    private function addonSlugFromKeyOrSlug(string $keyOrSlug): ?string
    {
        $keyOrSlug = $this->normalizeKey($keyOrSlug);
        if ($keyOrSlug === '') {
            return null;
        }

        $idx = $this->addonIndex();

        return $idx['keyToSlug'][$keyOrSlug] ?? null;
    }

    private function themeSlugFromKeyOrSlug(string $keyOrSlug): ?string
    {
        $keyOrSlug = $this->normalizeKey($keyOrSlug);
        if ($keyOrSlug === '') {
            return null;
        }

        $idx = $this->themeIndex();

        return $idx['keyToSlug'][$keyOrSlug] ?? null;
    }

    private function addonIndex(): array
    {
        if ($this->addonIndex !== null) {
            return $this->addonIndex;
        }

        $folder = (string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons');
        $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

        $folder = rtrim($folder, DS);
        if (!is_dir($folder)) {
            $this->addonIndex = ['versions' => [], 'keyToSlug' => [], 'slugs' => []];

            return $this->addonIndex;
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

        $this->addonIndex = [
            'versions' => $versions,
            'keyToSlug' => $keyToSlug,
            'slugs' => array_keys($slugs),
        ];

        return $this->addonIndex;
    }

    private function themeIndex(): array
    {
        if ($this->themeIndex !== null) {
            return $this->themeIndex;
        }

        $folder = (string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes');
        $manifestFile = (string)Configure::read('Update.themes.manifest', 'manifest.json');

        $folder = rtrim($folder, DS);
        if (!is_dir($folder)) {
            $this->themeIndex = ['versions' => [], 'keyToSlug' => [], 'slugs' => []];

            return $this->themeIndex;
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

        $this->themeIndex = [
            'versions' => $versions,
            'keyToSlug' => $keyToSlug,
            'slugs' => array_keys($slugs),
        ];

        return $this->themeIndex;
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

    private function cacheEnabled(string $kind): bool
    {
        $kind = $kind === 'themes' ? 'themes' : 'addons';

        return (bool)Configure::read('Update.' . $kind . '.cache.enabled', true);
    }

    private function cacheTtl(string $kind): int
    {
        $kind = $kind === 'themes' ? 'themes' : 'addons';

        $ttl = (int)Configure::read('Update.' . $kind . '.cache.ttl', 600);

        return $ttl > 0 ? $ttl : 0;
    }

    private function marketSources(string $kind): array
    {
        $kind = $kind === 'themes' ? 'themes' : 'addons';
        $cfg = Configure::read('Update.' . $kind . '.market');

        if (is_string($cfg)) {
            $cfg = [$cfg];
        }

        if (!is_array($cfg)) {
            return [];
        }

        $out = [];
        foreach ($cfg as $source) {
            $source = trim((string)$source);
            if ($source !== '') {
                $out[] = $source;
            }
        }

        return array_values(array_unique($out));
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
                return (string)file_get_contents($path);
            }

            return null;
        }

        if (is_file($source)) {
            return (string)file_get_contents($source);
        }

        $fallback = ROOT . DS . ltrim($source, DS);
        if (is_file($fallback)) {
            return (string)file_get_contents($fallback);
        }

        return null;
    }

    private function mergeSources(array $sources): array
    {
        $merged = [];
        $seen = [];

        foreach ($sources as $source) {
            $raw = $this->readSource($source);
            if (!is_string($raw) || $raw === '') {
                continue;
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $slug = strtolower(trim((string)($entry['slug'] ?? '')));
                if ($slug === '' || isset($seen[$slug])) {
                    continue;
                }

                $seen[$slug] = true;
                $merged[] = $entry;
            }
        }

        return $merged;
    }

    private function addonsMarketRaw(): array|false
    {
        if ($this->addonsMarket !== null) {
            return $this->addonsMarket;
        }

        $sources = $this->marketSources('addons');
        $cacheKey = 'update_market_addons_' . sha1(implode('|', $sources));

        if ($this->cacheEnabled('addons')) {
            $pool = Cache::pool('default');
            $cached = $pool->get($cacheKey);
            if (is_array($cached)) {
                $this->addonsMarket = $cached;

                return $this->addonsMarket;
            }
        }

        $this->addonsMarket = $this->mergeSources($sources);

        if ($this->cacheEnabled('addons')) {
            $ttl = $this->cacheTtl('addons');
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

        $sources = $this->marketSources('themes');
        $cacheKey = 'update_market_themes_' . sha1(implode('|', $sources));

        if ($this->cacheEnabled('themes')) {
            $pool = Cache::pool('default');
            $cached = $pool->get($cacheKey);
            if (is_array($cached)) {
                $this->themesMarket = $cached;

                return $this->themesMarket;
            }
        }

        $this->themesMarket = $this->mergeSources($sources);

        if ($this->cacheEnabled('themes')) {
            $ttl = $this->cacheTtl('themes');
            $pool = Cache::pool('default');
            $pool->set($cacheKey, $this->themesMarket, $ttl);
        }

        return $this->themesMarket;
    }
}
