<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string|null $sub_url
 * @property string|null $url
 * @property string|null $reason
 * @property bool $active
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 */
class Maintenance extends Entity
{
    protected array $_accessible = [
        'sub_url' => true,
        'url' => true,
        'reason' => true,
        'active' => true,
        'created_at' => false,
        'updated_at' => false,
    ];
}
