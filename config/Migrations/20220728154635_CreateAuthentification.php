<?php

use Migrations\AbstractMigration;

class CreateAuthentification extends AbstractMigration {
    public function change()
    {
        $table = $this->table('users__twofactorauth', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('user_id', 'integer', ['null' => false, 'default' => null, 'signed' => false]);
        $table->addColumn('secret', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('enabled', 'boolean', ['null' => false, 'default' => '1']);

        $table->create();
    }
}
