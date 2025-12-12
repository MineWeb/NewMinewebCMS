<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $title
 * @property string $content
 * @property string $slug
 * @property int $user_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $updated
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
        'created' => true,
        'updated' => true,
        'user' => true,
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
}
