<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int|null $order
 * @property string $title
 * @property string|null $extra
 * @property string|null $color
 * @property string $url
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 */
class SocialButton extends Entity
{
    protected array $_accessible = [
        'order' => true,
        'title' => true,
        'extra' => true,
        'color' => true,
        'url' => true,
        'created_at' => false,
        'updated_at' => false,
    ];
}
