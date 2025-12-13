<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\ConfigurationsTable;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;

final class ConfigurationService
{
    use LocatorAwareTrait;

    private ?ConfigurationsTable $Configurations = null;

    private function table(): ConfigurationsTable
    {
        if ($this->Configurations === null) {
            $this->Configurations = $this->fetchTable('Configurations');
        }

        return $this->Configurations;
    }

    public function get(string $key): mixed
    {
        $config = $this->table()->find()->first();

        return $config?->get($key);
    }

    public function set(string $key, mixed $value): bool
    {
        $config = $this->table()->find()->first()
            ?? $this->table()->newEmptyEntity();

        $config->set($key, $value);

        return (bool)$this->table()->save($config);
    }

    public function getWebsiteName(): string
    {
        $name = $this->get('name');

        return is_string($name) && $name !== '' ? $name : 'MineWeb';
    }

    public function getThemeName(): string
    {
        $theme = $this->get('theme');

        return is_string($theme) && $theme !== '' ? $theme : 'default';
    }
}
