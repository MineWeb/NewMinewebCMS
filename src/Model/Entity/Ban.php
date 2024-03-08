<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class Ban extends Entity
{
    protected function _getPseudo() {
        $UserTable = TableRegistry::getTableLocator()->get('User');
        $searchUser = $UserTable->find('all', conditions: ['id' => $this->user_id])->first();

        return $searchUser != null ? $searchUser['pseudo'] : 'N/A';
    }
}
