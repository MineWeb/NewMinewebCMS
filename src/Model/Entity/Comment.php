<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $content
 * @property int $user_id
 * @property int $news_id
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 *
 * @property \App\Model\Entity\User|null $user
 * @property \App\Model\Entity\News|null $news
 *
 * @property string $author
 */
class Comment extends Entity
{
    protected array $_accessible = [
        'content' => true,
        'user_id' => true,
        'news_id' => true,
        'user' => true,
        'news' => true,

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
        $user = $this->user;

        if ($user && isset($user->username)) {
            return (string)$user->username;
        }

        return 'N/A';
    }
}
