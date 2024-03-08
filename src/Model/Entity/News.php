<?php
namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class News extends Entity
{
    protected function _getCreated($created): string
    {
        $created = new \Cake\I18n\DateTime($created);
        return $created->toDateTimeString();
    }

    protected function _getUpdated($updated): string
    {
        $updated = new \Cake\I18n\DateTime($updated);
        return $updated->toDateTimeString();
    }

    protected function _getAuthor(): string
    {
        $UserTable = TableRegistry::getTableLocator()->get('User');
        $searchUser = $UserTable->find('all', conditions: ['id' => $this->user_id])->first();

        return $searchUser != null ? $searchUser['pseudo'] : 'N/A';
    }

    protected function _getLiked(): bool
    {
        $LikeTable = TableRegistry::getTableLocator()->get('Likes');
        $UserTable = TableRegistry::getTableLocator()->get('User');

        if (!$UserTable->isConnected())
            return false;

        return !empty($LikeTable->find('all', conditions: ['user_id' => $UserTable->getKey('id')])->first());
    }
 }
