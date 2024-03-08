<?php

use Migrations\AbstractMigration;

class CreateRank extends AbstractMigration {
    public function change()
    {
        $table = $this->table('ranks', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('rank_id', 'integer', ['null' => false, 'default' => null, 'signed' => false]);
        $table->addColumn('name', 'string', ['null' => false, 'length' => 20]);

        $table->create();
    }
}
