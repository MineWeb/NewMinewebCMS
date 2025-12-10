<?php

use Migrations\AbstractMigration;

class CreateServer extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('servers', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('name', 'string', ['null' => false, 'length' => 20])
            ->addColumn('ip', 'string', ['null' => false, 'length' => 120])
            ->addColumn('port', 'integer', ['null' => false, 'length' => 5, 'signed' => false])
            ->addColumn('type', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('data', 'string', ['null' => false, 'length' => 120])
            ->create();
    }
}
