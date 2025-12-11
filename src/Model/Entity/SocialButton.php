<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SocialButton extends Entity
{
    protected array $_accessible = [
        'order' => true,
        'title' => true,
        'extra' => true,
        'color' => true,
        'url' => true,
    ];

}
