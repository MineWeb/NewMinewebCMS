<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

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
