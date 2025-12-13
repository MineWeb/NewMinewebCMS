<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $rank_id
 * @property string $name
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 */
class Rank extends Entity
{
    protected array $_accessible = [
        'rank_id' => true,
        'name' => true,
        'created_at' => false,
        'updated_at' => false,
    ];
}
