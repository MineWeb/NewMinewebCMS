<?php

use Migrations\AbstractMigration;

class CreateNotification extends AbstractMigration {
    public function change()
    {
        $table = $this->table('notifications', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('group', 'string', ['length' => 10, 'null' => false, 'default' => 'user']);
        $table->addColumn('user_id', 'integer', ['null' => true, 'default' => null, 'signed' => false]);
        $table->addColumn('from', 'integer', ['null' => true, 'default' => null, 'signed' => false]);
        $table->addColumn('content', 'string', ['null' => false]);
        $table->addColumn('type', 'string', ['length' => 5, 'null' => false, 'default' => 'user']);
        $table->addColumn('seen', 'integer', ['length' => 1, 'null' => false, 'default' => 0, 'signed' => false]);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);

        $table->create();
    }
}
