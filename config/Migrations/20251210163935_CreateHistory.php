<?php

use Phinx\Migration\AbstractMigration;

class CreateHistory extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('histories', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('action', 'string', ['null' => false])
            ->addColumn('category', 'string', ['null' => false, 'length' => 50])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('user_id', 'integer', ['null' => false, 'length' => 20, 'signed' => false])
            ->addColumn('other', 'text', ['null' => true, 'default' => null])
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
