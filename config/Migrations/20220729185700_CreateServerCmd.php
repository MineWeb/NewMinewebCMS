<?php

use Migrations\AbstractMigration;

class CreateServerCmd extends AbstractMigration {
    public function change()
    {
        $table = $this->table('server_cmds', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('name', 'string', ['null' => false, 'length' => 255]);
        $table->addColumn('server_id', 'integer', ['null' => false, 'default' => null, 'length' => 8, 'signed' => false]);
        $table->addColumn('cmd', 'string', ['null' => false, 'length' => 255]);

        $table->create();
    }
}
