<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $email
 * @property string $key
 * @property \Cake\I18n\DateTime $created
 */
class Lostpassword extends Entity
{
    protected array $_accessible = [
        'email' => true,
        'key' => true,
        'created' => true,
    ];
}
