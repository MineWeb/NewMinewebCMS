<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $ip
 * @property int $count
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 */
class LoginRetry extends Entity
{
    protected array $_accessible = [
        'ip' => true,
        'count' => true,
        'created_at' => false,
        'updated_at' => false,
    ];
}
