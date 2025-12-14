<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\AddonService;
use App\Service\Package\PackageInfoService;
use Cake\View\Helper;

final class PluginHelper extends Helper
{
    private AddonService $addons;
    private PackageInfoService $info;

    public function __construct($view, array $config = [])
    {
        parent::__construct($view, $config);

        $this->addons = new AddonService();
        $this->info = new PackageInfoService();
    }

    public function pluginsLoaded(): object
    {
        return $this->addons->pluginsLoaded();
    }

    public function getPluginsActive(): object
    {
        return $this->addons->getPluginsActive();
    }

    public function findPlugin(string $key, mixed $value): mixed
    {
        return $this->addons->findPlugin($key, $value);
    }

    public function isInstalled(string $id): bool
    {
        return $this->addons->isInstalled($id);
    }

    public function findPluginsLinks(): array
    {
        return $this->addons->findPluginsLinks();
    }

    public function getPluginLastVersion(string $slug): string|false
    {
        $v = $this->info->addonLatestVersion($slug);

        return is_string($v) && $v !== '' ? $v : false;
    }

    public function getPluginsLastVersion(array $slugs): array|false
    {
        return $this->info->addonsLatestVersions($slugs);
    }

    public function getFreePlugins(bool $all = false, bool $removeInstalledPlugins = false): array|false
    {
        $installed = [];
        if ($removeInstalledPlugins) {
            foreach ((array)$this->addons->pluginsLoaded() as $p) {
                if (is_object($p) && isset($p->slug)) {
                    $installed[] = strtolower((string)$p->slug);
                }
            }
        }

        return $this->info->addonsMarketEntries($all, $removeInstalledPlugins, $installed);
    }

    public function getPluginFromAPI(string $slug): array|bool
    {
        return $this->info->addonMarketEntry($slug);
    }
}
