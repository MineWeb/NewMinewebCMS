<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\Package\PackageInfoService;
use Cake\Routing\Router;
use Cake\View\Helper;

final class UpdateHelper extends Helper
{
    private PackageInfoService $info;

    public function __construct($view, array $config = [])
    {
        parent::__construct($view, $config);

        $this->info = new PackageInfoService();
    }

    public function cmsVersion(): string
    {
        return $this->info->cmsCurrentVersion();
    }

    public function cmsLastVersion(): string
    {
        return $this->info->cmsLatestVersion();
    }

    public function cmsHasUpdate(): bool
    {
        return $this->info->cmsHasUpdate();
    }

    public function addonVersion(string $key): string
    {
        return $this->info->addonInstalledVersion($key) ?? '';
    }

    public function addonLastVersion(string $slug): string
    {
        return $this->info->addonLatestVersion($slug) ?? '';
    }

    public function addonHasUpdate(string $keyOrSlug): bool
    {
        return $this->info->addonHasUpdate($keyOrSlug);
    }

    public function themeVersion(string $key): string
    {
        return $this->info->themeInstalledVersion($key) ?? '';
    }

    public function themeLastVersion(string $slug): string
    {
        return $this->info->themeLatestVersion($slug) ?? '';
    }

    public function themeHasUpdate(string $keyOrSlug): bool
    {
        return $this->info->themeHasUpdate($keyOrSlug);
    }

    public function cmsAvailableHtml(): string
    {
        if (!$this->cmsHasUpdate()) {
            return '';
        }

        $url = Router::url(['_name' => 'admin_update_index']);

        return $this->alertHtml(
            __('UPDATE__AVAILABLE_TYPE_CMS') . ' ' . __('UPDATE__AVAILABLE') . ' ' . __('UPDATE__CMS_VERSION') . ' : ' . $this->cmsVersion() . ', ' . __('UPDATE__LAST_VERSION') . ' : ' . $this->cmsLastVersion(),
            $url,
            __('GLOBAL__UPDATE')
        );
    }

    public function pluginsAvailableHtml(): string
    {
        if (!$this->info->addonsHaveAnyUpdate()) {
            return '';
        }

        $url = Router::url(['_name' => 'admin_plugin_index']);

        return $this->alertHtml(
            __('UPDATE__AVAILABLE_TYPE_PLUGIN') . ' ' . __('UPDATE__AVAILABLE') . ' ' . __('UPDATE__PLUGIN'),
            $url,
            __('GLOBAL__UPDATE_LOOK')
        );
    }

    public function themesAvailableHtml(): string
    {
        if (!$this->info->themesHaveAnyUpdate()) {
            return '';
        }

        $url = Router::url(['_name' => 'admin_theme_index']);

        return $this->alertHtml(
            __('UPDATE__AVAILABLE_TYPE_THEME') . ' ' . __('UPDATE__AVAILABLE') . ' ' . __('UPDATE__THEME'),
            $url,
            __('GLOBAL__UPDATE_LOOK')
        );
    }

    public function availableUpdatesHtml(): string
    {
        return $this->cmsAvailableHtml() . $this->pluginsAvailableHtml() . $this->themesAvailableHtml();
    }

    private function alertHtml(string $message, string $url, string $buttonLabel): string
    {
        return "<div class='alert alert-secondary'>"
            . $message
            . " <a href='" . $url . "' style='margin-top: -6px;' class='btn float-right'>"
            . $buttonLabel
            . '</a>'
            . '</div>';
    }
}
