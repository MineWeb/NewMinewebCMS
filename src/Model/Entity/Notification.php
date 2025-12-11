<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

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
