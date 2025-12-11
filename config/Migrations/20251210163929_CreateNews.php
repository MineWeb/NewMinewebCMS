<?php

use Phinx\Migration\AbstractMigration;

class CreateNews extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('news', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('title', 'string', ['null' => false, 'length' => 50])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'length' => 20, 'signed' => false])
            ->addColumn('created', 'datetime', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated', 'datetime', ['null' => false])
            ->addColumn('img', 'string', ['null' => false])
            ->addColumn('slug', 'string', ['null' => false, 'length' => 150])
            ->addColumn('published', 'integer', ['null' => false, 'default' => 1, 'length' => 1, 'signed' => false])
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
