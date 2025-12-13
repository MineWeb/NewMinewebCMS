<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\Core\Configure;
use Cake\ORM\Entity;

class Role extends Entity
{
    protected array $_accessible = [
        'slug' => true,
        'name' => true,
        'permissions' => true,
        'is_default' => true,
        'is_system' => true,
        'sort' => true,
        'users' => true,
        'created_at' => false,
        'updated_at' => false,
    ];

    protected array $_virtual = [
        'display_name',
    ];

    protected function _getDisplayName(): string
    {
        $name = (string)($this->name ?? '');
        $slug = (string)($this->slug ?? '');
        $isSystem = (bool)($this->is_system ?? false);

        if (!$isSystem) {
            return $name;
        }

        $key = $this->systemTranslationKey($slug);
        if ($key === '') {
            return $name;
        }

        $translated = __($key);

        return $translated === $key ? $name : $translated;
    }

    private function systemTranslationKey(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return '';
        }

        $map = Configure::read('Roles.system_translation_keys', []);
        if (is_array($map) && isset($map[$slug])) {
            return (string)$map[$slug];
        }

        $normalized = strtoupper(preg_replace('/[^a-z0-9]+/i', '_', $slug) ?: '');
        $normalized = trim($normalized, '_');

        return $normalized === '' ? '' : 'ROLES__SYSTEM_' . $normalized;
    }
}
