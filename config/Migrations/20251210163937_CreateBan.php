<?php

use Migrations\AbstractMigration;

class CreateBan extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('bans', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('user_id', 'integer', ['null' => false, 'length' => 20, 'signed' => false])
            ->addColumn('reason', 'text', ['null' => false])
            ->addColumn('ip', 'string', ['null' => true, 'default' => null, 'length' => 50])
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
