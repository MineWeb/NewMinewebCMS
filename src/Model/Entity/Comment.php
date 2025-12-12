<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $content
 * @property \Cake\I18n\DateTime $created
 * @property int $user_id
 * @property int $news_id
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
        'created' => true,
        'user_id' => true,
        'news_id' => true,
        'user' => true,
        'news' => true,
    ];

    protected array $_virtual = [
        'author',
    ];

    protected function _getCreated(mixed $created): string
    {
        $created = new DateTime($created);

        return $created->toDateTimeString();
    }

    protected function _getAuthor(): string
    {
        $user = $this->user ?? null;

        if ($user && isset($user->pseudo)) {
            return (string)$user->pseudo;
        }

        return 'N/A';
    }
}
