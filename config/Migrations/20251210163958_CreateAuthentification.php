<?php

use Phinx\Migration\AbstractMigration;

class CreateAuthentification extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users__twofactorauth', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('user_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('secret', 'string', ['null' => false])
            ->addColumn('enabled', 'boolean', ['null' => false, 'default' => 1])
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
