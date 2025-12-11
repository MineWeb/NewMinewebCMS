<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

class Visit extends Entity
{
    protected array $_accessible = [
        'ip' => true,
        'created' => true,
        'referer' => true,
        'lang' => true,
        'navigator' => true,
        'page' => true,
        'user_id' => true,
        'user' => true,
    ];

    protected array $_virtual = [
        'author',
    ];

    protected function _getCreated(mixed $created): string
    {
        $created = new DateTime($created);

        return $created->toDateString();
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
