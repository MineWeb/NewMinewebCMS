<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Slider extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'subtitle' => true,
        'url_img' => true,
    ];
}
