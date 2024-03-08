<?php

use Migrations\AbstractMigration;

class CreateVisit extends AbstractMigration {
    public function change()
    {
        $table = $this->table('visits', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('ip', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('referer', 'text', ['null' => true, 'default' => null]);
        $table->addColumn('lang', 'string', ['null' => true, 'default' => 'fr', 'length' => 4]);
        $table->addColumn('navigator', 'string', ['null' => true, 'default' => null]);
        $table->addColumn('page', 'string', ['null' => true, 'default' => null]);

        $table->create();
    }
}
