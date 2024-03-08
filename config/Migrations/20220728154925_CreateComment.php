<?php

use Migrations\AbstractMigration;

class CreateComment extends AbstractMigration {
    public function change()
    {
        $table = $this->table('comments', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('content', 'text', ['null' => false, 'default' => null]);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('user_id', 'integer', ['null' => false, 'default' => null, 'length' => 20, 'signed' => false]);
        $table->addColumn('news_id', 'integer', ['null' => false, 'default' => null, 'length' => 20, 'signed' => false]);

        $table->create();
    }
}
