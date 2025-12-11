<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Seo extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'description' => true,
        'favicon_url' => true,
        'img_url' => true,
        'theme_color' => true,
        'twitter_site' => true,
        'page' => true,
    ];
}
