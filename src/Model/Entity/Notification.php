<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string|null $group
 * @property int $user_id
 * @property int|null $from
 * @property string $content
 * @property string|null $type
 * @property bool $seen
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 *
 * @property \App\Model\Entity\User|null $user
 * @property \App\Model\Entity\User|null $from_user
 */
class Notification extends Entity
{
    protected array $_accessible = [
        'group' => true,
        'user_id' => true,
        'from' => true,
        'content' => true,
        'type' => true,
        'seen' => true,
        'user' => true,
        'from_user' => true,
        'created_at' => false,
        'updated_at' => false,
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
