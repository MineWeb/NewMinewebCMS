<?php

use Migrations\AbstractMigration;

class CreatePage extends AbstractMigration {
    public function change()
    {
        $table = $this->table('pages', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('title', 'string', ['null' => false, 'default' => null, 'length' => 100]);
        $table->addColumn('content', 'text', ['null' => false, 'default' => null]);
        $table->addColumn('slug', 'string', ['null' => false, 'default' => null, 'length' => 150]);
        $table->addColumn('user_id', 'integer', ['null' => false, 'default' => null, 'length' => 20]);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('updated', 'datetime', ['null' => false, 'default' => null]);

        $table->create();
    }
}
