<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $action
 * @property string $category
 * @property \Cake\I18n\DateTime $created
 * @property int $user_id
 * @property string|null $other
 *
 * @property \App\Model\Entity\User|null $user
 * @property string $author
 */
class History extends Entity
{
    protected array $_accessible = [
        'action' => true,
        'category' => true,
        'created' => true,
        'user_id' => true,
        'other' => true,
        'user' => true,
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
