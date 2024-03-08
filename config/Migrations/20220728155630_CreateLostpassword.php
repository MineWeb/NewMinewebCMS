<?php

use Migrations\AbstractMigration;

class CreateLostpassword extends AbstractMigration {
    public function change()
    {
        $table = $this->table('lostpasswords', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('email', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('key', 'string', ['null' => false, 'default' => null, 'length' => 10]);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);

        $table->create();
    }
}
