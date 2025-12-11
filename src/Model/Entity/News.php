<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class News extends Entity
{
    protected array $_accessible = [
        'title' => true,
        'content' => true,
        'user_id' => true,
        'created' => true,
        'updated' => true,
        'img' => true,
        'slug' => true,
        'published' => true,
        'user' => true,
        'comments' => true,
        'likes' => true,
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

    protected function _getAuthor(): string
    {
        $UserTable = TableRegistry::getTableLocator()->get('Users');
        $searchUser = $UserTable->find('all', conditions: ['id' => $this->user_id])->first();

        return $searchUser != null ? $searchUser['pseudo'] : 'N/A';
    }

    protected function _getLiked(): bool
    {
        $LikeTable = TableRegistry::getTableLocator()->get('Likes');
        $UserTable = TableRegistry::getTableLocator()->get('Users');

        if (!$UserTable->isConnected()) {
            return false;
        }

        return !empty($LikeTable->find('all', conditions: ['user_id' => $UserTable->getKey('id')])->first());
    }
}
