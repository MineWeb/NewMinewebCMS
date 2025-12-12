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
 */
class Maintenance extends Entity
{
    protected array $_accessible = [
        'sub_url' => true,
        'url' => true,
        'reason' => true,
        'active' => true,
    ];
}
