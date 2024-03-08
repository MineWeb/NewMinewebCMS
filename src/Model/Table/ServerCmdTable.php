<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;

class ServerCmdTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('server_cmds');
    }
}
