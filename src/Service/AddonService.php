<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Package\PackageException;
use App\Service\Package\PackageInfoService;
use App\Service\Package\PackageManager;
use App\Service\Package\PackageManagerFactory;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

final class AddonService
{
    use LocatorAwareTrait;

    private ?object $pluginsLoaded = null;
    private ?PackageManager $packages = null;
    private ?PackageInfoService $info = null;

    private function packages(): PackageManager
    {
        if ($this->packages === null) {
            $this->packages = (new PackageManagerFactory())->create();
        }

        return $this->packages;
    }

    private function info(): PackageInfoService
    {
        if ($this->info === null) {
            $this->info = new PackageInfoService();
        }

        return $this->info;
    }

    public function pluginsLoaded(): object
    {
        if ($this->pluginsLoaded === null) {
            $this->pluginsLoaded = $this->loadPlugins();
        }

        return $this->pluginsLoaded;
    }

    public function reload(): object
    {
        $this->pluginsLoaded = $this->loadPlugins();

        return $this->pluginsLoaded;
    }

    public function loadPlugins(): object
    {
        $Plugins = $this->fetchTable('Plugins');
        $rows = $Plugins->find()->all();

        $out = (object)[];
        $loadedNow = (array)Configure::read('RuntimePlugins.addons', []);
        $pluginsFolder = (string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons');
        $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

        foreach ($rows as $row) {
            $slug = (string)$row->get('name');
            if ($slug === '') {
                continue;
            }

            $dir = rtrim($pluginsFolder, DS) . DS . $slug;
            $manifestPath = $dir . DS . $manifestFile;

            $data = (object)[
                'slug' => $slug,
                'slugLower' => strtolower($slug),
                'DBid' => (int)$row->get('id'),
                'DBinstall' => $row->get('created_at'),
                'active' => (bool)$row->get('state'),
                'loaded' => in_array($slug, $loadedNow, true),
                'isValid' => is_file($manifestPath),
            ];

            if ($data->isValid) {
                try {
                    $manifestRaw = (string)file_get_contents($manifestPath);
                    $manifest = json_decode($manifestRaw, false);
                    if (is_object($manifest)) {
                        foreach ($manifest as $k => $v) {
                            if (!property_exists($data, (string)$k)) {
                                $data->{(string)$k} = $v;
                            }
                        }
                        $data->id = strtolower((string)($manifest->author ?? '') . '.' . $slug);
                    }
                } catch (Throwable) {
                    $data->isValid = false;
                }
            }

            $id = isset($data->id) ? (string)$data->id : strtolower((string)$row->get('author') . '.' . $slug);
            $out->{$id} = $data;
        }

        return $out;
    }

    public function getPluginsActive(): object
    {
        $out = (object)[];

        foreach ((array)$this->pluginsLoaded() as $key => $plugin) {
            if (!is_object($plugin)) {
                continue;
            }

            if (empty($plugin->active) || empty($plugin->loaded) || empty($plugin->isValid)) {
                continue;
            }

            $out->{$key} = $plugin;
        }

        return $out;
    }

    public function findPlugin(string $key, mixed $value): mixed
    {
        $needle = is_string($value) ? strtolower(trim($value)) : $value;

        foreach ((array)$this->pluginsLoaded() as $plugin) {
            if (!is_object($plugin) || !isset($plugin->{$key})) {
                continue;
            }

            $candidate = $plugin->{$key};

            if (is_string($candidate) && is_string($needle)) {
                if (strtolower(trim($candidate)) === $needle) {
                    return $plugin;
                }
                continue;
            }

            if ($candidate == $needle) {
                return $plugin;
            }
        }

        return null;
    }

    public function isInstalled(string $id): bool
    {
        $id = strtolower(trim($id));
        if ($id === '') {
            return false;
        }

        $plugin = $this->findPlugin('id', $id);

        return is_object($plugin) && !empty($plugin->active) && !empty($plugin->loaded) && !empty($plugin->isValid);
    }

    public function findPluginsLinks(): array
    {
        $plugins = [];

        foreach ((array)$this->getPluginsActive() as $data) {
            if (!is_object($data)) {
                continue;
            }

            if (!isset($data->navbar_routes)) {
                continue;
            }

            $plugins[(string)$data->slug] = (object)[
                'name' => $data->name ?? $data->slug,
                'routes' => $data->navbar_routes,
            ];
        }

        return $plugins;
    }

    public function getPluginsLastVersion(array $slugs): array
    {
        return $this->info()->addonsLatestVersions($slugs);
    }

    public function getPluginLastVersion(string $slug): string|false
    {
        $v = $this->info()->addonLatestVersion($slug);

        return is_string($v) && $v !== '' ? $v : false;
    }

    public function getFreePlugins(bool $all = false, bool $removeInstalledPlugins = false): array|false
    {
        $installed = [];
        if ($removeInstalledPlugins) {
            foreach ((array)$this->pluginsLoaded() as $p) {
                if (is_object($p) && isset($p->slug)) {
                    $installed[] = strtolower((string)$p->slug);
                }
            }
        }

        return $this->info()->addonsMarketEntries($all, $removeInstalledPlugins, $installed);
    }

    public function getPluginFromAPI(string $slug): mixed
    {
        return $this->info()->addonMarketEntry($slug);
    }

    public function download(string $slug, bool $install = false): mixed
    {
        try {
            $entry = $this->info()->addonMarketEntry($slug);
            if ($entry === false || !is_array($entry)) {
                return 'ERROR__PLUGIN_CANT_BE_DOWNLOADED';
            }

            $this->packages()->installOrUpdateAddonFromMarket($slug, $entry, false);

            Cache::clearAll();
            $this->reload();

            return true;
        } catch (PackageException $e) {
            return $e->messageKey === 'ERROR__PACKAGE_NOT_COMPATIBLE' ? 'ERROR__PLUGIN_NOT_COMPATIBLE' : $e->messageKey;
        } catch (Throwable) {
            return 'ERROR__INTERNAL_ERROR';
        }
    }

    public function update(string $slug): mixed
    {
        try {
            $entry = $this->info()->addonMarketEntry($slug);
            if ($entry !== false && is_array($entry)) {
                $this->packages()->installOrUpdateAddonFromMarket($slug, $entry, true);
            } else {
                $this->packages()->updateAddon($slug);
            }

            Cache::clearAll();
            $this->reload();

            return true;
        } catch (PackageException $e) {
            return $e->messageKey;
        } catch (Throwable) {
            return 'ERROR__INTERNAL_ERROR';
        }
    }

    public function delete(string $slug): bool
    {
        try {
            $this->packages()->uninstallAddon($slug);

            Cache::clearAll();
            $this->reload();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function enable(int $dbID): bool
    {
        $Plugins = $this->fetchTable('Plugins');
        $entity = $Plugins->get($dbID);

        $entity->set('state', 1);
        $Plugins->save($entity);

        Cache::clearAll();
        $this->reload();

        return true;
    }

    public function disable(int $dbID): bool
    {
        $Plugins = $this->fetchTable('Plugins');
        $entity = $Plugins->get($dbID);

        $entity->set('state', 0);
        $Plugins->save($entity);

        Cache::clearAll();
        $this->reload();

        return true;
    }
}
