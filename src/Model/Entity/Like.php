<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $news_id
 * @property int $user_id
 *
 * @property \App\Model\Entity\News|null $news
 * @property \App\Model\Entity\User|null $user
 */
class Like extends Entity
{
    protected array $_accessible = [
        'news_id' => true,
        'user_id' => true,
        'news' => true,
        'user' => true,
    ];
}
