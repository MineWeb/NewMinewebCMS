<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LoginRetry extends Entity
{
    protected array $_accessible = [
        'ip' => true,
        'count' => true,
        'created' => true,
        'modified' => true,
    ];
}
