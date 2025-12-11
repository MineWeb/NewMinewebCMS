<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ServerCmd extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'server_id' => true,
        'cmd' => true,
        'server' => true,
    ];
}
