<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string|null $group
 * @property int $user_id
 * @property int|null $from
 * @property string $content
 * @property string|null $type
 * @property bool $seen
 * @property \Cake\I18n\DateTime $created
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
        'created' => true,
        'user' => true,
        'from_user' => true,
    ];

    protected function _getCreated(mixed $created): string
    {
        $created = new DateTime($created);

        return $created->toDateTimeString();
    }
}
