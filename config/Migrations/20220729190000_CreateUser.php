<?php

use Migrations\AbstractMigration;

class CreateUser extends AbstractMigration {
    public function change()
    {
        $table = $this->table('users', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('pseudo', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('uuid', 'string', ['null' => true, 'default' => null]);
        $table->addColumn('password', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('password_hash', 'string', ['null' => true, 'default' => null]);
        $table->addColumn('email', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('rank', 'integer', ['null' => false, 'default' => null, 'length' => 1, 'signed' => false]);
        $table->addColumn('money', 'float', ['null' => false, 'default' => 0, 'signed' => false]);
        $table->addColumn('ip', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('skin', 'integer', ['null' => false, 'default' => '0', 'length' => 1, 'signed' => false]);
        $table->addColumn('cape', 'integer', ['null' => false, 'default' => '0', 'length' => 1, 'signed' => false]);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('confirmed', 'string', ['length' => 25, 'null' => true, 'default' => null]);

        $table->create();
    }
}
