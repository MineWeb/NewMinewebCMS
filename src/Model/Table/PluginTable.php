<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;

class PluginTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable("plugins");
    }
}
