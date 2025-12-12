<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $rank_id
 * @property string $name
 */
class Rank extends Entity
{
    protected array $_accessible = [
        'rank_id' => true,
        'name' => true,
    ];
}
