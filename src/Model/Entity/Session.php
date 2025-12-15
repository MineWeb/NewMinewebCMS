<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property string $id
 * @property string|null $data
 * @property int|null $expires
 */
class Session extends Entity
{
    protected array $_accessible = [
        'id' => true,
        'data' => true,
        'expires' => true,
    ];
}
