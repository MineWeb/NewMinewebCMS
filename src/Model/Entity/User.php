<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $pseudo
 * @property string|null $uuid
 * @property string $password
 * @property string|null $password_hash
 * @property string $email
 * @property int|null $rank
 * @property float|null $money
 * @property string|null $ip
 * @property int|null $skin
 * @property int|null $cape
 * @property string|null $confirmed
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
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
        'confirmed' => true,
        'created_at' => false,
        'updated_at' => false,
    ];

    protected array $_hidden = [
        'password',
        'password_hash',
    ];

    protected function _getCreatedAt(mixed $createdAt): ?string
    {
        if ($createdAt === null) {
            return null;
        }

        if ($createdAt instanceof FrozenTime) {
            return $createdAt->toDateTimeString();
        }

        $time = FrozenTime::parse((string)$createdAt);

        return $time->toDateTimeString();
    }
}
