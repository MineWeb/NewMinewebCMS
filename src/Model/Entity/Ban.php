<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class Ban extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'reason' => true,
        'ip' => true,
        'user' => true,
    ];

    protected function _getPseudo(): string
    {
        $UserTable = TableRegistry::getTableLocator()->get('User');
        $searchUser = $UserTable->find('all', conditions: ['id' => $this->user_id])->first();

        return $searchUser != null ? $searchUser['pseudo'] : 'N/A';
    }
}
