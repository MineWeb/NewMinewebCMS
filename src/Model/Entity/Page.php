<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $slug
 * @property int $user_id
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 *
 * @property \App\Model\Entity\User|null $user
 */
class Page extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'content' => true,
        'slug' => true,
        'user_id' => true,
        'user' => true,
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

    protected function _getUpdatedAt(mixed $updatedAt): ?string
    {
        if ($updatedAt === null) {
            return null;
        }

        if ($updatedAt instanceof FrozenTime) {
            return $updatedAt->toDateTimeString();
        }

        $time = FrozenTime::parse((string)$updatedAt);

        return $time->toDateTimeString();
    }
}
