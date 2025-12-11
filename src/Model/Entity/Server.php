<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Server extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'ip' => true,
        'port' => true,
        'type' => true,
        'data' => true,
        'server_cmds' => true,
        'api_configurations' => true,
    ];
}
