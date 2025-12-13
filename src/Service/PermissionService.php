<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;

final class PermissionService
{
    use LocatorAwareTrait;

    public const ADMIN_RANK_IDS = [3, 4];

    private array $rankCache = [];

    public function list(): array
    {
        $list = Configure::read('Permissions.list', []);

        if (is_array($list)) {
            return array_values(array_unique(array_filter(array_map('strval', $list), static fn(string $v): bool => $v !== '')));
        }

        return [];
    }

    public function have(int $rankId, string $permission): bool
    {
        if (in_array($rankId, self::ADMIN_RANK_IDS, true)) {
            return true;
        }

        $permissions = $this->getRankPermissions($rankId);

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function getRankPermissions(int $rankId): array
    {
        if (isset($this->rankCache[$rankId])) {
            return $this->rankCache[$rankId];
        }

        $this->rankCache[$rankId] = $this->loadRankPermissions($rankId);

        return $this->rankCache[$rankId];
    }

    public function clearCache(?int $rankId = null): void
    {
        if ($rankId === null) {
            $this->rankCache = [];
            return;
        }

        unset($this->rankCache[$rankId]);
    }

    public function getAllPermissionsMatrix(?array $rankIds = null): array
    {
        $permissionsList = $this->list();

        $rankIds = $rankIds ?? $this->getAllRankIds();

        $matrix = [];
        foreach ($permissionsList as $permission) {
            $matrix[$permission] = [];
            foreach ($rankIds as $rankId) {
                $matrix[$permission][(int)$rankId] = $this->have((int)$rankId, $permission);
            }
        }

        return $matrix;
    }

    public function getAllRankIds(): array
    {
        $rankIds = [0, 2];

        $Ranks = $this->fetchTable('Ranks');
        foreach ($Ranks->find()->all() as $rank) {
            $rid = (int)($rank->rank_id ?? 0);
            if ($rid > 0) {
                $rankIds[] = $rid;
            }
        }

        $rankIds = array_values(array_unique($rankIds));
        sort($rankIds);

        return $rankIds;
    }

    private function loadRankPermissions(int $rankId): array
    {
        if ($rankId <= 0) {
            return [];
        }

        $Permissions = $this->fetchTable('Permissions');

        $row = $Permissions
            ->find()
            ->where(['rank' => $rankId])
            ->first();

        if ($row === null) {
            return [];
        }

        $raw = null;
        if (isset($row->permissions)) {
            $raw = $row->permissions;
        } elseif (isset($row->perms)) {
            $raw = $row->perms;
        }

        return $this->normalizePermissions($raw);
    }

    private function normalizePermissions(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }

        if (is_array($raw)) {
            return $this->cleanList($raw);
        }

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $unserialized = @unserialize($raw);
        if (is_array($unserialized)) {
            return $this->cleanList($unserialized);
        }

        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $this->cleanList($json);
        }

        $parts = preg_split('/[\s,;|]+/', $raw) ?: [];

        return $this->cleanList($parts);
    }

    private function cleanList(array $list): array
    {
        $out = [];
        foreach ($list as $v) {
            if (!is_string($v) && !is_int($v) && !is_float($v)) {
                continue;
            }

            $s = trim((string)$v);
            if ($s === '') {
                continue;
            }

            $out[] = $s;
        }

        return array_values(array_unique($out));
    }
}
