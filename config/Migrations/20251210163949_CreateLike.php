<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLike extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('likes');

        $table
            ->addColumn('news_id', 'integer', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addTimestamps()
            ->addIndex(['news_id'])
            ->addIndex(['user_id'])
            ->addForeignKey('news_id', 'news', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

    }
}
