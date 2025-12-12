<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $secret
 * @property bool|int $enabled
 *
 * @property \App\Model\Entity\User|null $user
 */
class UsersTwofactorauth extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'secret' => true,
        'enabled' => true,
        'user' => true,
    ];

    protected array $_hidden = [
        'secret',
    ];
}
