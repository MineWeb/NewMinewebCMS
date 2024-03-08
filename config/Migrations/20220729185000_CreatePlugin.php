<?php

use Migrations\AbstractMigration;

class CreatePlugin extends AbstractMigration {
    public function change()
    {
        $table = $this->table('plugins', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('name', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('author', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('version', 'string', ['null' => false, 'default' => null, 'length' => 20]);
        $table->addColumn('state', 'integer', ['null' => false, 'default' => '1', 'length' => 1, 'signed' => false]);

        $table->create();
    }
}
