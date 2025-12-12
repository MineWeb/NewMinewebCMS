<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;

final class ConfigurationService
{
    use LocatorAwareTrait;

    public function get(string $key): mixed
    {
        $Configurations = $this->fetchTable('Configurations');
        $config = $Configurations->find()->first();

        return $config?->get($key);
    }

    public function getWebsiteName(): string
    {
        $name = $this->get('name');

        return is_string($name) && $name !== '' ? $name : 'MineWeb';
    }

    public function getThemeName(): string
    {
        $theme = Configure::read('theme');

        return is_string($theme) && $theme !== '' ? $theme : 'default';
    }
}
