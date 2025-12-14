<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\HttpService;
use App\Service\Package\Filesystem\FilesystemService;
use App\Service\Package\Manifest\ManifestLoader;
use App\Service\Package\PackageException;
use App\Service\Package\PackageManager;
use App\Service\Package\Requirement\RequirementChecker;
use App\Service\Package\Source\GitHubSource;
use App\Service\Package\UpdateStateStore;
use App\Service\PermissionSynchronizer;
use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Throwable;

final class EyPluginComponent extends Component
{
    use \Cake\ORM\Locator\LocatorAwareTrait;

    public string $pluginsFolder;
    public object $pluginsLoaded;

    private PackageManager $packages;
    private string $marketUrl;

    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        parent::__construct($registry, $config);

        $this->pluginsFolder = (string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons');
        $this->marketUrl = (string)Configure::read('Update.addons.market', '');

        $http = new HttpService();
        $fs = new FilesystemService();
        $github = new GitHubSource($http);
        $manifests = new ManifestLoader($github);
        $permSync = new PermissionSynchronizer();
        $state = new UpdateStateStore(ROOT . DS . 'tmp' . DS . 'update' . DS . 'state.json');
        $requirements = new RequirementChecker(fn() => $this->packages?->cmsCurrentVersion() ?? '0.0.0');

        $this->packages = new PackageManager(
            $http,
            $fs,
            $github,
            $manifests,
            $permSync,
            $requirements,
            $state
        );

        $this->pluginsLoaded = (object)[];
    }

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $controller = $this->getController();
        if ($controller) {
            $controller->set('EyPlugin', $this);
        }

        $this->pluginsLoaded = $this->loadPlugins();
    }

    public function loadPlugins(): object
    {
        $Plugins = $this->fetchTable('Plugins');
        $rows = $Plugins->find()->all();

        $out = (object)[];
        $loadedNow = (array)Configure::read('RuntimePlugins.addons', []);
        $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

        foreach ($rows as $row) {
            $slug = (string)$row->get('name');
            if ($slug === '') {
                continue;
            }

            $dir = rtrim($this->pluginsFolder, DS) . DS . $slug;
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

        foreach ((array)$this->pluginsLoaded as $key => $plugin) {
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

        foreach ((array)$this->pluginsLoaded as $plugin) {
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

    public function displayAvailableUpdate(): ?string
    {
        foreach ((array)$this->pluginsLoaded as $plugin) {
            if (!is_object($plugin) || empty($plugin->slug) || empty($plugin->version)) {
                continue;
            }

            $last = $this->getPluginLastVersion((string)$plugin->slug);
            if (is_string($last) && $last !== '' && (string)$plugin->version !== $last) {
                return '<div class="alert alert-secondary">'
                    . __('UPDATE__AVAILABLE_TYPE_PLUGIN') . ' '
                    . __('UPDATE__AVAILABLE') . ' '
                    . __('UPDATE__PLUGIN')
                    . ' <a href="' . Router::url(['_name' => 'admin_plugin_index']) . '" style="margin-top: -6px;" class="btn float-right">'
                    . __('GLOBAL__UPDATE_LOOK')
                    . '</a></div>';
            }
        }

        return null;
    }

    public function getPluginsLastVersion(array $slugs): array|false
    {
        $slugs = array_values(array_unique(array_filter(array_map(
            static fn($v) => trim((string)$v),
            $slugs
        ), static fn($v) => $v !== '')));

        if ($slugs === []) {
            return [];
        }

        $all = $this->getFreePlugins(true, false);
        if ($all === false) {
            return false;
        }

        $map = [];
        foreach ($all as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $slug = (string)($entry['slug'] ?? '');
            $version = (string)($entry['version'] ?? '');

            if ($slug === '' || $version === '') {
                continue;
            }

            $map[strtolower($slug)] = $version;
        }

        $out = [];
        foreach ($slugs as $slug) {
            $key = strtolower($slug);
            if (isset($map[$key])) {
                $out[$slug] = $map[$key];
            }
        }

        return $out;
    }

    public function getPluginLastVersion(string $slug): string|false
    {
        $entry = $this->getPluginFromAPI($slug);
        if (!is_array($entry)) {
            return false;
        }

        if (isset($entry['version']) && is_string($entry['version']) && $entry['version'] !== '') {
            return $entry['version'];
        }

        return false;
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

    public function getFreePlugins(bool $all = false, bool $removeInstalledPlugins = false): array|false
    {
        $raw = $this->marketUrl !== '' ? (new HttpService())->sendGetRequest($this->marketUrl) : '';
        $list = json_decode($raw, true);
        if (!is_array($list)) {
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
            $installed = [];
            foreach ((array)$this->pluginsLoaded as $p) {
                if (is_object($p) && isset($p->slug)) {
                    $installed[] = strtolower((string)$p->slug);
                }
            }

            $plugins = array_values(array_filter($plugins, static function ($p) use ($installed) {
                $slug = strtolower((string)($p['slug'] ?? ''));

                return $slug !== '' && !in_array($slug, $installed, true);
            }));
        }

        return $plugins;
    }

    public function getPluginFromAPI(string $slug): mixed
    {
        $all = $this->getFreePlugins(true, false);
        if ($all === false) {
            return false;
        }

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

    public function download(string $slug, bool $install = false): mixed
    {
        try {
            $entry = $this->getPluginFromAPI($slug);
            if ($entry === false) {
                return 'ERROR__PLUGIN_CANT_BE_DOWNLOADED';
            }

            $this->packages->installAddonFromMarket($slug, (array)$entry);

            Cache::clearAll();
            $this->pluginsLoaded = $this->loadPlugins();

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
            $this->packages->updateAddon($slug);

            Cache::clearAll();
            $this->pluginsLoaded = $this->loadPlugins();

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
            $this->packages->uninstallAddon($slug);

            Cache::clearAll();
            $this->pluginsLoaded = $this->loadPlugins();

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

        return true;
    }

    public function disable(int $dbID): bool
    {
        $Plugins = $this->fetchTable('Plugins');
        $entity = $Plugins->get($dbID);

        $entity->set('state', 0);
        $Plugins->save($entity);

        Cache::clearAll();

        return true;
    }
}
