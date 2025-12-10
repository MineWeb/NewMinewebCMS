<?php

use Migrations\AbstractMigration;

class CreateUser extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('pseudo', 'string', ['null' => false])
            ->addColumn('uuid', 'string', ['null' => true, 'default' => null])
            ->addColumn('password', 'string', ['null' => false])
            ->addColumn('password_hash', 'string', ['null' => true, 'default' => null])
            ->addColumn('email', 'string', ['null' => false])
            ->addColumn('rank', 'integer', ['null' => false, 'length' => 1, 'signed' => false])
            ->addColumn('money', 'float', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('ip', 'string', ['null' => false, 'length' => 50])
            ->addColumn('skin', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('cape', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('confirmed', 'string', ['length' => 25, 'null' => true, 'default' => null])
            ->create();
    }
}
