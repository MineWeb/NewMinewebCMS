<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;

class SeoTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable("seo");
    }
}
