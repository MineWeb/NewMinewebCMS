<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property string $url_img
 */
class Slider extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'subtitle' => true,
        'url_img' => true,
    ];
}
