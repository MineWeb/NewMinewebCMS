<?php

use Migrations\AbstractMigration;

class CreatePage extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('pages', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('title', 'string', ['null' => false, 'length' => 100])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('slug', 'string', ['null' => false, 'length' => 150])
            ->addColumn('user_id', 'integer', ['null' => false, 'length' => 20, 'signed' => false])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated', 'datetime', ['null' => false])
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
