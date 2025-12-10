<?php

use Migrations\AbstractMigration;

class CreateLike extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('likes', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('news_id', 'integer', ['null' => false, 'length' => 20, 'signed' => false])
            ->addColumn('user_id', 'integer', ['null' => false, 'length' => 20, 'signed' => false])
            ->addIndex(['news_id'])
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('news_id', 'news', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
