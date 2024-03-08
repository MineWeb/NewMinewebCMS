<?php

use Migrations\AbstractMigration;

class CreateLoginRetry extends AbstractMigration {
    public function change()
    {
        $table = $this->table('login_retries', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('ip', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('count', 'integer', ['null' => false, 'default' => null, 'length' => 11, 'signed' => false]);
        $table->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('modified', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP']);

        $table->create();
    }
}
