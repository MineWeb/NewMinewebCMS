<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\AddonService;
use Cake\View\Helper;
use Cake\View\View;

final class PluginHelper extends Helper
{
    private AddonService $addons;

    public function __construct(View $view, array $config = [])
    {
        parent::__construct($view, $config);

        $this->addons = new AddonService();
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
        return $this->addons->getPluginLastVersion($slug);
    }

    public function getPluginsLastVersion(array $slugs): array|false
    {
        return $this->addons->getPluginsLastVersion($slugs);
    }

    public function getFreePlugins(bool $all = false, bool $removeInstalledPlugins = false): array|false
    {
        return $this->addons->getFreePlugins($all, $removeInstalledPlugins);
    }

    public function getPluginFromAPI(string $slug): mixed
    {
        return $this->addons->getPluginFromAPI($slug);
    }
}
