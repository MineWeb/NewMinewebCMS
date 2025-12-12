<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property int $user_id
 * @property string $img
 * @property string $slug
 * @property bool $published
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 *
 * @property \App\Model\Entity\User|null $user
 * @property iterable<\App\Model\Entity\Comment>|\Cake\Collection\CollectionInterface|null $comments
 * @property iterable<\App\Model\Entity\Like>|\Cake\Collection\CollectionInterface|null $likes
 *
 * @property string $author
 */
class News extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'content' => true,
        'user_id' => true,
        'img' => true,
        'slug' => true,
        'published' => true,
        'user' => true,
        'comments' => true,
        'likes' => true,

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

    protected function _getAuthor(): string
    {
        $user = $this->user;

        if ($user && isset($user->pseudo)) {
            return (string)$user->pseudo;
        }

        return 'N/A';
    }

    public function isLikedBy(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        $likes = $this->likes;
        if (!is_iterable($likes)) {
            return false;
        }

        foreach ($likes as $like) {
            if (isset($like->user_id) && (int)$like->user_id === $userId) {
                return true;
            }
        }

        return false;
    }
}
