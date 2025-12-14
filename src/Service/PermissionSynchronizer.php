<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;

final class PermissionSynchronizer
{
    use LocatorAwareTrait;

    public function applyDefaults(array $defaults): void
    {
        if ($defaults === []) {
            return;
        }

        $Roles = $this->fetchTable('Roles');

        foreach ($defaults as $roleKey => $perms) {
            if (!is_array($perms)) {
                continue;
            }

            $role = null;

            if (is_numeric($roleKey)) {
                $role = $Roles->find()->where(['id' => (int)$roleKey])->first();
            } else {
                $role = $Roles->find()->where(['slug' => (string)$roleKey])->first();
            }

            if ($role === null) {
                continue;
            }

            $raw = (string)($role->get('permissions') ?? '[]');
            $decoded = json_decode($raw, true);
            $existing = is_array($decoded) ? $decoded : [];

            $merged = [];

            foreach ($existing as $p) {
                $p = trim((string)$p);
                if ($p !== '') {
                    $merged[$p] = true;
                }
            }

            foreach ($perms as $p) {
                $p = trim((string)$p);
                if ($p !== '') {
                    $merged[$p] = true;
                }
            }

            $role->set('permissions', json_encode(array_keys($merged), JSON_UNESCAPED_UNICODE));
            $Roles->save($role);
        }
    }

    public function refreshAllowedPermissions(array $allowed): void
    {
        $allowed = array_values(array_unique(array_filter(array_map(
            static fn($v) => trim((string)$v),
            $allowed
        ), static fn($v) => $v !== '')));

        sort($allowed);

        $Roles = $this->fetchTable('Roles');
        $roles = $Roles->find()->select(['id', 'permissions'])->all();

        foreach ($roles as $role) {
            $raw = (string)($role->get('permissions') ?? '[]');
            $decoded = json_decode($raw, true);
            $perms = is_array($decoded) ? $decoded : [];

            $clean = [];
            $seen = [];

            foreach ($perms as $p) {
                $p = trim((string)$p);
                if ($p === '') {
                    continue;
                }

                if ($p === '*') {
                    if (!isset($seen['*'])) {
                        $seen['*'] = true;
                        $clean[] = '*';
                    }
                    continue;
                }

                if (!in_array($p, $allowed, true)) {
                    continue;
                }

                if (isset($seen[$p])) {
                    continue;
                }

                $seen[$p] = true;
                $clean[] = $p;
            }

            $role->set('permissions', json_encode($clean, JSON_UNESCAPED_UNICODE));
            $Roles->save($role);
        }
    }

    public function corePermissions(): array
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
}
