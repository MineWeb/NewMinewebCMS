<?php

use Migrations\AbstractMigration;

class CreateVisit extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('visits', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('ip', 'string', ['null' => false, 'length' => 50])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('referer', 'text', ['null' => true, 'default' => null])
            ->addColumn('lang', 'string', ['null' => true, 'default' => 'fr', 'length' => 4])
            ->addColumn('navigator', 'string', ['null' => true, 'default' => null])
            ->addColumn('page', 'string', ['null' => true, 'default' => null])
            ->create();
    }
}
