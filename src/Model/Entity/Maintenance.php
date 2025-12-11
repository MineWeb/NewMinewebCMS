<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Maintenance extends Entity
{
    protected array $_accessible = [
        'sub_url' => true,
        'url' => true,
        'reason' => true,
        'active' => true,
    ];
}
