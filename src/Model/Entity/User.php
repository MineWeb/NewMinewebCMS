<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'username' => true,
        'uuid' => true,
        'password' => true,
        'password_hash' => true,
        'email' => true,
        'role_id' => true,
        'money' => true,
        'ip' => true,
        'skin' => true,
        'cape' => true,
        'confirmed' => true,
        'created_at' => false,
        'updated_at' => false,
        'role' => false,
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
