<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Plugin extends Entity
{
    protected array $_accessible = [
        'created' => true,
        'name' => true,
        'author' => true,
        'version' => true,
        'state' => true,
    ];
}
