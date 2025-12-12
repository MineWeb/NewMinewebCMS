<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property int $user_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $updated
 * @property string $img
 * @property string $slug
 * @property bool $published
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
        'created' => true,
        'updated' => true,
        'img' => true,
        'slug' => true,
        'published' => true,
        'user' => true,
        'comments' => true,
        'likes' => true,
    ];

    protected array $_virtual = [
        'author',
    ];

    protected function _getCreated(mixed $created): string
    {
        $created = new DateTime($created);

        return $created->toDateTimeString();
    }

    protected function _getUpdated(mixed $updated): string
    {
        $updated = new DateTime($updated);

        return $updated->toDateTimeString();
    }

    protected function _getAuthor(): string
    {
        $user = $this->user ?? null;

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

        $likes = $this->likes ?? null;
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
