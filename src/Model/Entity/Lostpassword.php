<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Lostpassword extends Entity
{
    protected array $_accessible = [
        'email' => true,
        'key' => true,
        'created' => true,
    ];
}
