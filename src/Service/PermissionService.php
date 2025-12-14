<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

final class PermissionService
{
    use LocatorAwareTrait;

    private array $roleCache = [];
    private ?array $permissionsCache = null;

    public function list(): array
    {
        if ($this->permissionsCache !== null) {
            return $this->permissionsCache;
        }

        $core = Configure::read('Permissions.list', []);
        $core = is_array($core) ? $core : [];

        $out = [];
        foreach ($core as $v) {
            $s = trim((string)$v);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        if (InstallState::isInstalled()) {
            try {
                $Plugins = $this->fetchTable('Plugins');
                $rows = $Plugins->find()->select(['name'])->all();

                $addonsFolder = (string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons');
                $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

                foreach ($rows as $row) {
                    $slug = (string)$row->get('name');
                    if ($slug === '') {
                        continue;
                    }

                    $path = rtrim($addonsFolder, DS) . DS . $slug . DS . $manifestFile;
                    if (!is_file($path)) {
                        continue;
                    }

                    $raw = (string)file_get_contents($path);
                    $m = json_decode($raw, true);
                    if (!is_array($m)) {
                        continue;
                    }

                    $available = $m['permissions']['available'] ?? [];
                    if (!is_array($available)) {
                        continue;
                    }

                    foreach ($available as $p) {
                        $p = trim((string)$p);
                        if ($p !== '') {
                            $out[] = $p;
                        }
                    }
                }
            } catch (Throwable) {
            }
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $this->permissionsCache = $out;
    }

    public function isSuper(int $roleId): bool
    {
        return in_array('*', $this->getRolePermissions($roleId), true);
    }

    public function canRole(int $roleId, string $permission): bool
    {
        $permissions = $this->getRolePermissions($roleId);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function getRolePermissions(int $roleId): array
    {
        if ($roleId <= 0) {
            return [];
        }

        if (!InstallState::isInstalled()) {
            return [];
        }

        if (isset($this->roleCache[$roleId])) {
            return $this->roleCache[$roleId];
        }

        try {
            $Roles = $this->fetchTable('Roles');
            $role = $Roles
                ->find()
                ->select(['id', 'permissions'])
                ->where(['id' => $roleId])
                ->first();
        } catch (Throwable) {
            return $this->roleCache[$roleId] = [];
        }

        if ($role === null) {
            return $this->roleCache[$roleId] = [];
        }

        $raw = (string)($role->permissions ?? '[]');
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return $this->roleCache[$roleId] = [];
        }

        $out = [];
        foreach ($decoded as $v) {
            $s = trim((string)$v);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return $this->roleCache[$roleId] = array_values(array_unique($out));
    }

    public function clearCache(?int $roleId = null): void
    {
        if ($roleId === null) {
            $this->roleCache = [];
            $this->permissionsCache = null;

            return;
        }

        unset($this->roleCache[$roleId]);
    }

    public function getAllPermissionsMatrix(array $roleIds): array
    {
        $permissionsList = $this->list();

        $matrix = [];
        foreach ($permissionsList as $permission) {
            $matrix[$permission] = [];
            foreach ($roleIds as $roleId) {
                $matrix[$permission][(int)$roleId] = $this->canRole((int)$roleId, $permission);
            }
        }

        return $matrix;
    }
}
