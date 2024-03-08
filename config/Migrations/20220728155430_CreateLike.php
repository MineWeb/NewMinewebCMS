<?php

use Migrations\AbstractMigration;

class CreateLike extends AbstractMigration {
    public function change()
    {
        $table = $this->table('likes', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('news_id', 'integer', ['null' => false, 'default' => null, 'length' => 20, 'signed' => false]);
        $table->addColumn('user_id', 'integer', ['null' => false, 'default' => null, 'length' => 20, 'signed' => false]);

        $table->create();
    }
}
