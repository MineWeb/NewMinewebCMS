<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $ip
 * @property int $count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class LoginRetry extends Entity
{
    protected array $_accessible = [
        'ip' => true,
        'count' => true,
        'created' => true,
        'modified' => true,
    ];
}
