<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class Visit extends Entity
{
    protected array $_accessible = [
        'ip' => true,
        'created' => true,
        'referer' => true,
        'lang' => true,
        'navigator' => true,
        'page' => true,
    ];

    protected function _getCreated(mixed $created): string
    {
        $created = new DateTime($created);

        return $created->toDateString();
    }

    protected function _getAuthor(): string
    {
        $UserTable = TableRegistry::getTableLocator()->get('User');
        $searchUser = $UserTable->find('all', conditions: ['id' => $this->user_id])->first();

        return $searchUser != null ? $searchUser['pseudo'] : 'N/A';
    }
}
