<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $rank
 * @property string $permissions
 */
class Permission extends Entity
{
    protected array $_accessible = [
        'rank' => true,
        'permissions' => true,
    ];
}
