<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $name
 * @property int $server_id
 * @property string $cmd
 *
 * @property \App\Model\Entity\Server|null $server
 */
class ServerCmd extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'server_id' => true,
        'cmd' => true,
        'server' => true,
    ];
}
