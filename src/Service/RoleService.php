<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

final class RoleService
{
    use LocatorAwareTrait;

    public const ADMIN_SLUG = 'admin';

    public function getAll(): array
    {
        return $this->fetchTable('Roles')
            ->find()
            ->orderBy(['sort' => 'ASC', 'id' => 'ASC'])
            ->all()
            ->toArray();
    }
}
