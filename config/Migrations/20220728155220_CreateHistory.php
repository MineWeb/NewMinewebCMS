<?php

use Migrations\AbstractMigration;

class CreateHistory extends AbstractMigration {
    public function change()
    {
        $table = $this->table('histories', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('action', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('category', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('user_id', 'integer', ['null' => false, 'default' => null, 'length' => 20, 'signed' => false]);
        $table->addColumn('other', 'text', ['null' => true, 'default' => null]);

        $table->create();
    }
}
