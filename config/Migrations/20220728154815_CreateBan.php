<?php

use Migrations\AbstractMigration;

class CreateBan extends AbstractMigration {
    public function change()
    {
        $table = $this->table('bans', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('user_id', 'integer', ['null' => false, 'default' => null, 'length' => 20, 'signed' => false]);
        $table->addColumn('reason', 'text', ['null' => false, 'default' => null]);
        $table->addColumn('ip', 'string', ['null' => true, 'default' => null, 'length' => 50]);

        $table->create();
    }
}
