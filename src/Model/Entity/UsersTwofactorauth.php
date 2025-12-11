<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

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
