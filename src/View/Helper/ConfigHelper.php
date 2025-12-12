<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\ConfigurationService;
use Cake\View\Helper;

final class ConfigHelper extends Helper
{
    private ConfigurationService $config;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->config = new ConfigurationService();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->config->get($key);

        return $value ?? $default;
    }

    public function websiteName(string $default = 'MineWeb'): string
    {
        $name = $this->config->getWebsiteName();

        return $name !== '' ? $name : $default;
    }

    public function themeName(string $default = 'default'): string
    {
        $theme = $this->config->getThemeName();

        return $theme !== '' ? $theme : $default;
    }
}
