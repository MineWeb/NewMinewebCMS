<?php

use Phinx\Migration\AbstractMigration;

class CreateLoginRetry extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('login_retries', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('ip', 'string', ['null' => false, 'length' => 50])
            ->addColumn('count', 'integer', ['null' => false, 'length' => 11, 'signed' => false])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('modified', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}
