<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\HttpService;
use App\Service\Package\PackageException;
use App\Service\Package\PackageManager;
use App\Service\Package\PackageManagerFactory;
use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Routing\Router;
use Throwable;

final class ThemeComponent extends Component
{
    public string $themesFolder;
    private string $marketUrl;
    private PackageManager $packages;

    private array $themesAvailable = [];
    private array $themesInstalled = [];

    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);

        $this->themesFolder = (string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes');
        $this->marketUrl = (string)Configure::read('Update.themes.market', '');

        $this->packages = (new PackageManagerFactory())->create();
    }

    public function getThemesOnAPI(bool $all = true, bool $deleteInstalledThemes = false): array
    {
        $key = $all ? 'all' : 'free';
        if (isset($this->themesAvailable[$key])) {
            return $this->themesAvailable[$key];
        }

        $raw = $this->marketUrl !== '' ? (new HttpService())->sendGetRequest($this->marketUrl) : '';
        $list = json_decode($raw, true);
        if (!is_array($list)) {
            return $this->themesAvailable[$key] = [];
        }

        $themes = [];
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (!$all && empty($entry['free'])) {
                continue;
            }

            $themes[] = $entry;
        }

        if ($deleteInstalledThemes) {
            $installed = $this->getThemesInstalled(false);
            $installedSlugs = [];
            foreach ($installed as $t) {
                $installedSlugs[] = strtolower((string)($t->slug ?? ''));
            }

            $themes = array_values(array_filter($themes, static function ($t) use ($installedSlugs) {
                $slug = strtolower((string)($t['slug'] ?? ''));

                return $slug !== '' && !in_array($slug, $installedSlugs, true);
            }));
        }

        return $this->themesAvailable[$key] = $themes;
    }

    public function getThemesInstalled(bool $api = true): object
    {
        if (isset($this->themesInstalled[$api ? 1 : 0])) {
            return $this->themesInstalled[$api ? 1 : 0];
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

            $manifestPath = $path . DS . Configure::read('Update.themes.manifest', 'manifest.json');
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

            if ($api) {
                $apiTheme = $this->getThemeFromAPI((string)$m->slug);
                if (is_array($apiTheme) && isset($apiTheme['version'])) {
                    $m->lastVersion = (string)$apiTheme['version'];
                }
            }

            $out->{$id} = $m;
        }

        return $this->themesInstalled[$api ? 1 : 0] = $out;
    }

    private function getThemeFromAPI(string $slug): mixed
    {
        $all = $this->getThemesOnAPI(true, false);
        foreach ($all as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            if (strcasecmp((string)($entry['slug'] ?? ''), $slug) === 0) {
                return $entry;
            }
        }

        return false;
    }

    public function install(string $slug, bool $update = false)
    {
        try {
            $entry = $this->getThemeFromAPI($slug);
            if ($entry === false) {
                return 'THEME__ERROR_INSTALL_DOWNLOAD_FAILED';
            }

            $repo = (string)($entry['repo'] ?? '');
            if ($repo === '') {
                $repo = sprintf((string)Configure::read('Update.themes.repoPattern'), $slug);
            }

            $this->packages->installOrUpdateTheme($slug, $repo, $update);

            Cache::clearAll();

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

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function displayAvailableUpdate(): ?string
    {
        $themes = $this->getThemesInstalled(true);
        foreach ($themes as $value) {
            if (isset($value->lastVersion) && isset($value->version) && (string)$value->version !== (string)$value->lastVersion) {
                return '<div class="alert alert-secondary">'
                    . __('UPDATE__AVAILABLE_TYPE_THEME') . ' '
                    . __('UPDATE__AVAILABLE') . ' '
                    . __('UPDATE__THEME')
                    . '<a href="'
                    . Router::url(['_name' => 'admin_theme_index'])
                    . '" style="margin-top: -6px;" class="btn float-right">'
                    . __('GLOBAL__UPDATE_LOOK')
                    . '</a></div>';
            }
        }

        return null;
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
                if (isset($t->slug) && (string)$t->slug === $slug) {
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

    public function getCurrentTheme(): array
    {
        $controller = $this->getController();
        $configuredTheme = $controller->Configuration->get('theme');

        $installed = $this->getThemesInstalled(false);
        foreach ($installed as $t) {
            if (isset($t->slug) && (string)$t->slug === (string)$configuredTheme) {
                $cfg = $this->readThemeOverrideConfig((string)$t->slug) ?? [];

                return [(string)$t->slug, $cfg];
            }
        }

        $defaultPath = ROOT . DS . 'config' . DS . 'theme.default.json';
        $defaultCfg = is_file($defaultPath) ? json_decode((string)file_get_contents($defaultPath), true) : [];
        if (!is_array($defaultCfg)) {
            $defaultCfg = [];
        }

        return ['default', $defaultCfg];
    }

    public function getPath(string $slug): string
    {
        return rtrim($this->themesFolder, DS) . DS . $slug;
    }
}
