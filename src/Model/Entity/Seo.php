<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $favicon_url
 * @property string|null $img_url
 * @property string|null $theme_color
 * @property string|null $twitter_site
 * @property string|null $page
 */
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
