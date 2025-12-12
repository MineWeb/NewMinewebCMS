<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $name
 * @property string $ip
 * @property int $port
 * @property string|null $type
 * @property string|null $data
 *
 * @property iterable<\App\Model\Entity\ServerCmd>|\Cake\Collection\CollectionInterface|null $server_cmds
 * @property iterable<\App\Model\Entity\ApiConfiguration>|\Cake\Collection\CollectionInterface|null $api_configurations
 */
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
