<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $ip
 * @property string|null $referer
 * @property string|null $lang
 * @property string|null $navigator
 * @property string|null $page
 * @property int|null $user_id
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 *
 * @property \App\Model\Entity\User|null $user
 * @property string $author
 */
class Visit extends Entity
{
    protected array $_accessible = [
        'ip' => true,
        'referer' => true,
        'lang' => true,
        'navigator' => true,
        'page' => true,
        'user_id' => true,
        'user' => true,
        'created_at' => false,
        'updated_at' => false,
    ];

    protected array $_virtual = [
        'author',
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

    protected function _getAuthor(): string
    {
        $user = $this->user ?? null;

        if ($user && isset($user->username)) {
            return (string)$user->username;
        }

        return 'N/A';
    }
}
