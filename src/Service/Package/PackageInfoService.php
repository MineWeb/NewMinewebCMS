<?php
declare(strict_types=1);

namespace App\Service\Package;

use App\Service\HttpService;
use Cake\Core\Configure;

final class PackageInfoService
{
    private PackageManager $packages;
    private HttpService $http;

    private mixed $addonsMarket = null;
    private mixed $themesMarket = null;

    private ?array $addonsMarketMap = null;
    private ?array $themesMarketMap = null;

    private ?array $addonIndex = null;
    private ?array $themeIndex = null;

    public function __construct(?PackageManagerFactory $factory = null, ?HttpService $http = null)
    {
        $factory ??= new PackageManagerFactory();

        $this->packages = $factory->create();
        $this->http = $http ?? new HttpService();
    }

    public function cmsCurrentVersion(): string
    {
        return $this->packages->cmsCurrentVersion();
    }

    public function cmsLatestVersion(): string
    {
        return $this->packages->cmsLatestVersion() ?: $this->cmsCurrentVersion();
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

    public function addonLatestVersion(string $slug): ?string
    {
        $slug = $this->normalizeKey($slug);
        if ($slug === '') {
            return null;
        }

        $map = $this->addonsMarketVersionMap();

        return $map[$slug] ?? null;
    }

    public function addonsLatestVersions(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter(array_map(
            static fn($v) => trim((string)$v),
            $slugs
        ), static fn($v) => $v !== '')));

        if ($slugs === []) {
            return [];
        }

        $map = $this->addonsMarketVersionMap();

        $out = [];
        foreach ($slugs as $slug) {
            $k = $this->normalizeKey($slug);
            if ($k !== '' && isset($map[$k])) {
                $out[$slug] = $map[$k];
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

    public function addonsHaveAnyUpdate(): bool
    {
        $idx = $this->addonIndex();
        $map = $this->addonsMarketVersionMap();

        foreach ($idx['slugs'] as $slugLower) {
            $installed = $idx['versions'][$slugLower] ?? null;
            $latest = $map[$slugLower] ?? null;

            if (!is_string($installed) || $installed === '' || !is_string($latest) || $latest === '') {
                continue;
            }

            if (version_compare($installed, $latest, '<')) {
                return true;
            }
        }

        return false;
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

    public function themeLatestVersion(string $slug): ?string
    {
        $slug = $this->normalizeKey($slug);
        if ($slug === '') {
            return null;
        }

        $map = $this->themesMarketVersionMap();

        return $map[$slug] ?? null;
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

    public function themesHaveAnyUpdate(): bool
    {
        $idx = $this->themeIndex();
        $map = $this->themesMarketVersionMap();

        foreach ($idx['slugs'] as $slugLower) {
            $installed = $idx['versions'][$slugLower] ?? null;
            $latest = $map[$slugLower] ?? null;

            if (!is_string($installed) || $installed === '' || !is_string($latest) || $latest === '') {
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

        $plugins = [];
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (!$all && empty($entry['free'])) {
                continue;
            }

            $plugins[] = $entry;
        }

        if ($removeInstalledPlugins) {
            $installedSlugsLower = array_values(array_unique(array_filter(array_map(
                static fn($v) => strtolower(trim((string)$v)),
                $installedSlugsLower
            ), static fn($v) => $v !== '')));

            if ($installedSlugsLower !== []) {
                $plugins = array_values(array_filter($plugins, static function ($p) use ($installedSlugsLower) {
                    $slug = strtolower((string)($p['slug'] ?? ''));

                    return $slug !== '' && !in_array($slug, $installedSlugsLower, true);
                }));
            }
        }

        return $plugins;
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
                return $entry;
            }
        }

        return false;
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
            $m = $this->readManifest($path);
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
            $m = $this->readManifest($path);
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

    private function readManifest(string $path): ?array
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

    private function addonsMarketRaw(): array|false
    {
        if ($this->addonsMarket !== null) {
            return $this->addonsMarket;
        }

        $url = (string)Configure::read('Update.addons.market', '');
        if ($url === '') {
            $this->addonsMarket = [];

            return $this->addonsMarket;
        }

        $raw = $this->http->sendGetRequest($url);
        $decoded = json_decode((string)$raw, true);

        $this->addonsMarket = is_array($decoded) ? $decoded : false;

        return $this->addonsMarket;
    }

    private function themesMarketRaw(): array|false
    {
        if ($this->themesMarket !== null) {
            return $this->themesMarket;
        }

        $url = (string)Configure::read('Update.themes.market', '');
        if ($url === '') {
            $this->themesMarket = [];

            return $this->themesMarket;
        }

        $raw = $this->http->sendGetRequest($url);
        $decoded = json_decode((string)$raw, true);

        $this->themesMarket = is_array($decoded) ? $decoded : false;

        return $this->themesMarket;
    }

    private function addonsMarketVersionMap(): array
    {
        if ($this->addonsMarketMap !== null) {
            return $this->addonsMarketMap;
        }

        $list = $this->addonsMarketRaw();
        if ($list === false) {
            $this->addonsMarketMap = [];

            return $this->addonsMarketMap;
        }

        $map = [];
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $slug = $this->normalizeKey((string)($entry['slug'] ?? ''));
            $version = trim((string)($entry['version'] ?? ''));

            if ($slug === '' || $version === '') {
                continue;
            }

            $map[$slug] = $version;
        }

        $this->addonsMarketMap = $map;

        return $this->addonsMarketMap;
    }

    private function themesMarketVersionMap(): array
    {
        if ($this->themesMarketMap !== null) {
            return $this->themesMarketMap;
        }

        $list = $this->themesMarketRaw();
        if ($list === false) {
            $this->themesMarketMap = [];

            return $this->themesMarketMap;
        }

        $map = [];
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $slug = $this->normalizeKey((string)($entry['slug'] ?? ''));
            $version = trim((string)($entry['version'] ?? ''));

            if ($slug === '' || $version === '') {
                continue;
            }

            $map[$slug] = $version;
        }

        $this->themesMarketMap = $map;

        return $this->themesMarketMap;
    }
}
