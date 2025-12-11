<?php

use Phinx\Migration\AbstractMigration;

class CreateLostpassword extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('lostpasswords', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('email', 'string', ['null' => false, 'length' => 50])
            ->addColumn('key', 'string', ['null' => false, 'length' => 10])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->create();
    }
}
