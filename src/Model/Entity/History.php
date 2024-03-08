<?php
namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class History extends Entity
{
    protected function _getCreated($created): string
    {
        $created = new \Cake\I18n\DateTime($created);
        return $created->toDateTimeString();
    }

    protected function _getAuthor(): string
    {
        $UserTable = TableRegistry::getTableLocator()->get('User');
        $searchUser = $UserTable->find('all', conditions: ['id' => $this->user_id])->first();

        return $searchUser != null ? $searchUser['pseudo'] : 'N/A';
    }
}
