<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\ConfigurationsTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

final class ConfigurationService
{
    use LocatorAwareTrait;

    private ?ConfigurationsTable $Configurations = null;

    private ?EntityInterface $cachedEntity = null;
    private ?array $cachedAll = null;

    private function table(): ConfigurationsTable
    {
        if ($this->Configurations === null) {
            $this->Configurations = $this->fetchTable('Configurations');
        }

        return $this->Configurations;
    }

    private function loadFirstEntity(): ?EntityInterface
    {
        if ($this->cachedEntity !== null) {
            return $this->cachedEntity;
        }

        $this->cachedEntity = $this->table()->find()->first();

        return $this->cachedEntity;
    }

    public function get(int|string $key): mixed
    {
        if (is_int($key)) {
            try {
                return $this->table()->get($key);
            } catch (Throwable) {
                return null;
            }
        }

        $config = $this->loadFirstEntity();

        return $config?->get($key);
    }

    public function getEntity(): EntityInterface
    {
        $entity = $this->loadFirstEntity();

        if ($entity !== null) {
            return $entity;
        }

        $entity = $this->table()->newEmptyEntity();
        $this->cachedEntity = $entity;

        return $entity;
    }

    public function getAll(): ?array
    {
        if ($this->cachedAll !== null) {
            return $this->cachedAll;
        }

        $entity = $this->loadFirstEntity();
        if ($entity === null) {
            return null;
        }

        $this->cachedAll = $entity->toArray();

        return $this->cachedAll;
    }

    public function set(string $key, mixed $value): bool
    {
        $config = $this->loadFirstEntity() ?? $this->table()->newEmptyEntity();

        $config->set($key, $value);

        $saved = (bool)$this->table()->save($config);

        if ($saved) {
            $this->cachedEntity = $config;
            $this->cachedAll = null;
        }

        return $saved;
    }

    public function saveOrFail(EntityInterface $entity): EntityInterface
    {
        $saved = $this->table()->saveOrFail($entity);

        $this->cachedEntity = $saved;
        $this->cachedAll = null;

        return $saved;
    }

    public function clearCache(): void
    {
        $this->cachedEntity = null;
        $this->cachedAll = null;
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
