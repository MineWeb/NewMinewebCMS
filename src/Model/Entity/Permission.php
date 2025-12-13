<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $rank
 * @property string $permissions
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 */
class Permission extends Entity
{
    protected array $_accessible = [
        'rank' => true,
        'permissions' => true,
        'created_at' => false,
        'updated_at' => false,
    ];
}
