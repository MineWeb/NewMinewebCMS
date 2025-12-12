<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $pseudo
 * @property string|null $uuid
 * @property string $password
 * @property string $password_hash
 * @property string $email
 * @property string|int|null $rank
 * @property int|float|string|null $money
 * @property string|null $ip
 * @property string|null $skin
 * @property string|null $cape
 * @property \Cake\I18n\DateTime $created
 * @property bool|int|null $confirmed
 */
class User extends Entity
{
    protected array $_accessible = [
        'pseudo' => true,
        'uuid' => true,
        'password' => true,
        'password_hash' => true,
        'email' => true,
        'rank' => true,
        'money' => true,
        'ip' => true,
        'skin' => true,
        'cape' => true,
        'created' => true,
        'confirmed' => true,
    ];

    protected array $_hidden = [
        'password',
        'password_hash',
    ];

    protected function _getCreated(mixed $created): string
    {
        $created = new DateTime($created);

        return $created->toDateTimeString();
    }
}
