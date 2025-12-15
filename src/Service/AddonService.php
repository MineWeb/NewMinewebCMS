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
                        $data->id = strtolower(($manifest->author ?? '') . '.' . $slug);
                    }
                } catch (Throwable) {
                    $data->isValid = false;
                }
            }

            $id = $data->id ?? strtolower((string)$row->get('author') . '.' . $slug);
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

            $routes = $this->normalizeNavbarRoutes(
                $data->navbar_routes ?? null,
                $data->slug ?? '',
                $data->name ?? ''
            );

            if ($routes === []) {
                continue;
            }

            $plugins[(string)$data->slug] = (object)[
                'name' => $data->name ?? $data->slug,
                'routes' => $routes,
            ];
        }

        return $plugins;
    }

    public function findPluginsAdminMenus(): array
    {
        $out = [];

        foreach ((array)$this->getPluginsActive() as $data) {
            if (!is_object($data)) {
                continue;
            }

            $menus = $this->normalizeAdminMenus(
                $data->admin_menus ?? null,
                (string)($data->slug ?? ''),
                (string)($data->name ?? '')
            );

            if ($menus === []) {
                continue;
            }

            foreach ($menus as $m) {
                $out[] = $m;
            }
        }

        usort($out, static function (array $a, array $b): int {
            $ia = isset($a['index']) && is_int($a['index']) ? $a['index'] : PHP_INT_MAX;
            $ib = isset($b['index']) && is_int($b['index']) ? $b['index'] : PHP_INT_MAX;

            if ($ia !== $ib) {
                return $ia <=> $ib;
            }

            return strcmp((string)($a['label'] ?? ''), (string)($b['label'] ?? ''));
        });

        return $out;
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

    private function normalizeNavbarRoutes(mixed $routes, string $pluginSlug, string $pluginName): array
    {
        $routes = $this->toArray($routes);
        if ($routes === []) {
            return [];
        }

        $out = [];

        foreach ($routes as $k => $v) {
            $item = $this->normalizeNavbarRouteItem($k, $v, $pluginSlug, $pluginName);
            if ($item === null) {
                continue;
            }

            $key = $this->routeKey($item['route'] ?? null);
            if ($key === '') {
                continue;
            }

            $out[$key] = $item;
        }

        return array_values($out);
    }

    private function normalizeNavbarRouteItem(int|string $k, mixed $v, string $pluginSlug, string $pluginName): ?array
    {
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') {
                return null;
            }

            return [
                'label' => $this->fallbackLabel($k, $pluginName, $pluginSlug, $v),
                'route' => ['_name' => $v],
            ];
        }

        if (is_object($v)) {
            $v = (array)$v;
        }

        if (!is_array($v)) {
            return null;
        }

        $label = isset($v['label']) ? trim((string)$v['label']) : '';
        $route = $v['route'] ?? null;

        if ($label === '') {
            $label = $this->fallbackLabel($k, $pluginName, $pluginSlug, '');
        }

        $route = $this->normalizeRouteToUrlArray($route);
        if ($route === null) {
            return null;
        }

        $out = [
            'label' => $label,
            'route' => $route,
        ];

        if (isset($v['icon'])) {
            $icon = trim((string)$v['icon']);
            if ($icon !== '') {
                $out['icon'] = $icon;
            }
        }

        if (isset($v['permission'])) {
            $perm = trim((string)$v['permission']);
            if ($perm !== '') {
                $out['permission'] = $perm;
            }
        }

        return $out;
    }

    private function normalizeAdminMenus(mixed $menus, string $pluginSlug, string $pluginName): array
    {
        $menus = $this->toArray($menus);
        if ($menus === []) {
            return [];
        }

        $out = [];
        foreach ($menus as $k => $v) {
            $node = $this->normalizeAdminMenuGroup($k, $v, $pluginSlug, $pluginName);
            if ($node === null) {
                continue;
            }
            $out[] = $node;
        }

        return $out;
    }

    private function normalizeAdminMenuGroup(int|string $k, mixed $v, string $pluginSlug, string $pluginName): ?array
    {
        if (is_object($v)) {
            $v = (array)$v;
        }

        if (!is_array($v)) {
            return null;
        }

        $label = isset($v['label']) ? trim((string)$v['label']) : '';
        if ($label === '') {
            $label = $this->fallbackLabel($k, $pluginName, $pluginSlug, 'PLUGIN__MENU');
        }

        $icon = isset($v['icon']) ? trim((string)$v['icon']) : '';
        $permission = isset($v['permission']) ? trim((string)$v['permission']) : '';

        $index = null;
        if (isset($v['index']) && (is_int($v['index']) || is_string($v['index']))) {
            $idx = (int)$v['index'];
            if ($idx >= 0) {
                $index = $idx;
            }
        }

        $menu = $this->normalizeAdminMenuItems($v['menu'] ?? null, $pluginSlug, $pluginName);
        if ($menu === []) {
            return null;
        }

        $out = [
            'label' => $label,
            'icon' => $icon,
            'menu' => $menu,
        ];

        if ($index !== null) {
            $out['index'] = $index;
        }

        if ($permission !== '') {
            $out['permission'] = $permission;
        }

        return $out;
    }

    private function normalizeAdminMenuItems(mixed $items, string $pluginSlug, string $pluginName): array
    {
        $items = $this->toArray($items);
        if ($items === []) {
            return [];
        }

        $out = [];

        foreach ($items as $k => $v) {
            $node = $this->normalizeAdminMenuItem($k, $v, $pluginSlug, $pluginName);
            if ($node === null) {
                continue;
            }
            $out[] = $node;
        }

        return $out;
    }

    private function normalizeAdminMenuItem(int|string $k, mixed $v, string $pluginSlug, string $pluginName): ?array
    {
        if (is_object($v)) {
            $v = (array)$v;
        }

        if (is_string($v)) {
            $v = ['route' => $v];
        }

        if (!is_array($v)) {
            return null;
        }

        $label = isset($v['label']) ? trim((string)$v['label']) : '';
        if ($label === '') {
            $label = $this->fallbackLabel($k, $pluginName, $pluginSlug, 'PLUGIN__MENU_ITEM');
        }

        $icon = isset($v['icon']) ? trim((string)$v['icon']) : '';
        $permission = isset($v['permission']) ? trim((string)$v['permission']) : '';
        $route = $this->normalizeRouteToUrlArray($v['route'] ?? null);

        $menu = $this->normalizeAdminMenuItems($v['menu'] ?? null, $pluginSlug, $pluginName);

        if ($route === null && $menu === []) {
            return null;
        }

        $out = [
            'label' => $label,
            'icon' => $icon,
        ];

        if ($permission !== '') {
            $out['permission'] = $permission;
        }

        if ($route !== null) {
            $out['route'] = $route;
        }

        if ($menu !== []) {
            $out['menu'] = $menu;
        }

        return $out;
    }

    private function normalizeRouteToUrlArray(mixed $route): ?array
    {
        if (is_string($route)) {
            $route = trim($route);
            if ($route === '') {
                return null;
            }

            if (str_starts_with($route, '/')) {
                return ['_path' => $route];
            }

            return ['_name' => $route];
        }

        if (is_array($route)) {
            return $route === [] ? null : $route;
        }

        return null;
    }

    private function routeKey(mixed $route): string
    {
        if (!is_array($route) || $route === []) {
            return '';
        }

        return sha1(json_encode($route, JSON_UNESCAPED_UNICODE));
    }

    private function fallbackLabel(int|string $k, string $pluginName, string $pluginSlug, string $fallback): string
    {
        if (is_string($k)) {
            $k = trim($k);
            if ($k !== '' && !ctype_digit($k)) {
                return $k;
            }
        }

        $fallback = trim($fallback);
        if ($fallback !== '') {
            return $fallback;
        }

        $pluginName = trim($pluginName);
        if ($pluginName !== '') {
            return $pluginName;
        }

        $pluginSlug = trim($pluginSlug);

        return $pluginSlug !== '' ? $pluginSlug : 'Link';
    }

    private function toArray(mixed $v): array
    {
        if ($v === null) {
            return [];
        }

        if (is_object($v)) {
            $v = (array)$v;
        }

        if (!is_array($v)) {
            return [];
        }

        return $v;
    }
}
