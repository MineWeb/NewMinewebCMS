<?php

use Phinx\Migration\AbstractMigration;

class CreateRank extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('ranks', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('rank_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('name', 'string', ['null' => false, 'length' => 20])
            ->create();
    }
}
