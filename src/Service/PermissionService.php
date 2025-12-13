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

    public function list(): array
    {
        $list = Configure::read('Permissions.list', []);
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $v) {
            $s = trim((string)$v);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $out;
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
