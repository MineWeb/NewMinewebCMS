<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Session extends Entity
{
    protected array $_accessible = [
        'id' => true,
        'created' => true,
        'modified' => true,
        'data' => true,
        'expires' => true,
    ];

    protected array $_hidden = [
        'data',
    ];
}
