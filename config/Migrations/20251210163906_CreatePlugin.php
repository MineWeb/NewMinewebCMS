<?php

use Phinx\Migration\AbstractMigration;

class CreatePlugin extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('plugins', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('name', 'string', ['null' => false, 'length' => 50])
            ->addColumn('author', 'string', ['null' => false, 'length' => 50])
            ->addColumn('version', 'string', ['null' => false, 'length' => 20])
            ->addColumn('state', 'integer', ['null' => false, 'default' => 1, 'length' => 1, 'signed' => false])
            ->create();
    }
}
