<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $email
 * @property string $key
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 */
class Lostpassword extends Entity
{
    protected array $_accessible = [
        'email' => true,
        'key' => true,
        'created_at' => false,
        'updated_at' => false,
    ];
}
