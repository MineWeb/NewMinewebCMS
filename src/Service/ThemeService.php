<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Package\PackageException;
use App\Service\Package\PackageInfoService;
use App\Service\Package\PackageManager;
use App\Service\Package\PackageManagerFactory;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Throwable;

final class ThemeService
{
    private PackageManager $packages;
    private PackageInfoService $info;

    private string $themesFolder;
    private string $manifestFile;

    private ?object $themesInstalledNoApi = null;
    private ?object $themesInstalledWithApi = null;

    public function __construct(?PackageManager $packages = null, ?PackageInfoService $info = null)
    {
        $this->packages = $packages ?? (new PackageManagerFactory())->create();
        $this->info = $info ?? new PackageInfoService();

        $this->themesFolder = (string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes');
        $this->manifestFile = (string)Configure::read('Update.themes.manifest', 'manifest.json');
    }

    public function getThemesOnAPI(bool $all = true, bool $deleteInstalledThemes = false): array
    {
        $installedSlugs = [];
        if ($deleteInstalledThemes) {
            foreach ((array)$this->getThemesInstalled(false) as $t) {
                if (is_object($t) && isset($t->slug)) {
                    $installedSlugs[] = strtolower((string)$t->slug);
                }
            }
        }

        $list = $this->info->themesMarketEntries($all, $deleteInstalledThemes, $installedSlugs);

        return is_array($list) ? $list : [];
    }

    public function getThemesInstalled(bool $api = true): object
    {
        if ($api && $this->themesInstalledWithApi !== null) {
            return $this->themesInstalledWithApi;
        }

        if (!$api && $this->themesInstalledNoApi !== null) {
            return $this->themesInstalledNoApi;
        }

        $dir = rtrim($this->themesFolder, DS);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $entries = scandir($dir) ?: [];
        $out = (object)[];

        foreach ($entries as $slug) {
            if (!is_string($slug) || $slug === '.' || $slug === '..' || $slug === '.gitkeep') {
                continue;
            }

            $path = $dir . DS . $slug;
            if (!is_dir($path)) {
                continue;
            }

            $manifestPath = $path . DS . $this->manifestFile;
            if (!is_file($manifestPath)) {
                continue;
            }

            $raw = (string)file_get_contents($manifestPath);
            $m = json_decode($raw);
            if (!is_object($m) || empty($m->slug)) {
                continue;
            }

            $id = strtolower((string)($m->author ?? '') . '.' . (string)$m->slug);
            $m->id = $id;

            if ($api && isset($m->slug)) {
                $last = $this->info->themeLatestVersion((string)$m->slug);
                if (is_string($last) && $last !== '') {
                    $m->lastVersion = $last;
                }
            }

            $out->{$id} = $m;
        }

        if ($api) {
            $this->themesInstalledWithApi = $out;

            return $this->themesInstalledWithApi;
        }

        $this->themesInstalledNoApi = $out;

        return $this->themesInstalledNoApi;
    }

    public function install(string $slug, bool $update = false): mixed
    {
        try {
            $entry = $this->info->themeMarketEntry($slug);
            if ($entry === false || !is_array($entry)) {
                return 'THEME__ERROR_INSTALL_DOWNLOAD_FAILED';
            }

            $this->packages->installOrUpdateThemeFromMarket($slug, $entry, $update);

            Cache::clearAll();
            $this->themesInstalledNoApi = null;
            $this->themesInstalledWithApi = null;

            return true;
        } catch (PackageException $e) {
            return $e->messageKey === 'ERROR__PACKAGE_NOT_COMPATIBLE' ? 'THEME__ERROR_NOT_COMPATIBLE' : $e->messageKey;
        } catch (Throwable) {
            return 'THEME__ERROR_INSTALL_UNZIP';
        }
    }

    public function delete(string $slug): bool
    {
        try {
            $this->packages->uninstallTheme($slug);

            Cache::clearAll();
            $this->themesInstalledNoApi = null;
            $this->themesInstalledWithApi = null;

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function getCustomData(string $slug): array
    {
        $config = [];
        $themeName = $slug;

        if ($slug === 'default') {
            $path = ROOT . DS . 'config' . DS . 'theme.default.json';
            if (is_file($path)) {
                $decoded = json_decode((string)file_get_contents($path), true);
                if (is_array($decoded)) {
                    $config = $decoded;
                }
            }
            $themeName = 'Bootstrap';
        } else {
            $installed = $this->getThemesInstalled(false);
            foreach ($installed as $t) {
                if (is_object($t) && isset($t->slug) && (string)$t->slug === $slug) {
                    $themeName = (string)($t->name ?? $slug);
                    break;
                }
            }

            $override = $this->readThemeOverrideConfig($slug);
            if (is_array($override)) {
                $config = $override;
            }
        }

        return [$themeName, $config];
    }

    public function processCustomData(string $slug, ServerRequest $request): bool
    {
        try {
            $data = $request->getData();
            if (!is_array($data)) {
                $data = [];
            }

            if ($slug === 'default') {
                $path = ROOT . DS . 'config' . DS . 'theme.default.json';
                file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                return true;
            }

            $this->writeThemeOverrideConfig($slug, $data);

            return true;
        } catch (Throwable $e) {
            Log::error('Theme config save failed: ' . $e->getMessage());

            return false;
        }
    }

    public function themeOverrideConfig(string $slug): array
    {
        return $this->readThemeOverrideConfig($slug) ?? [];
    }

    public function getPath(string $slug): string
    {
        return rtrim($this->themesFolder, DS) . DS . $slug;
    }

    private function themeOverrideConfigPath(string $slug): string
    {
        return ROOT . DS . 'config' . DS . 'themes' . DS . strtolower($slug) . '.json';
    }

    private function readThemeOverrideConfig(string $slug): ?array
    {
        $path = $this->themeOverrideConfigPath($slug);
        if (!is_file($path)) {
            return null;
        }

        $decoded = json_decode((string)file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function writeThemeOverrideConfig(string $slug, array $data): void
    {
        $path = $this->themeOverrideConfigPath($slug);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
