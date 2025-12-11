<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Like extends Entity
{
    protected array $_accessible = [
        'news_id' => true,
        'user_id' => true,
        'news' => true,
        'user' => true,
    ];
}
