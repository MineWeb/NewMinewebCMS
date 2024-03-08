<?php

use Migrations\AbstractMigration;

class CreateServer extends AbstractMigration {
    public function change()
    {
        $table = $this->table('servers', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('name', 'string', ['null' => false, 'length' => 20]);
        $table->addColumn('ip', 'string', ['null' => false, 'length' => 120]);
        $table->addColumn('port', 'integer', ['null' => false, 'default' => null, 'length' => 5, 'signed' => false]);
        $table->addColumn('type', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false]);
        $table->addColumn('data', 'string', ['null' => false, 'length' => 120]);

        $table->create();
    }
}
