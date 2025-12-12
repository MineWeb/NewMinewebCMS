<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateComment extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('comments');

        $table
            ->addColumn('content', 'text', ['null' => false])
            ->addTimestamps()
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('news_id', 'integer', ['null' => false])
            ->addIndex(['user_id'])
            ->addIndex(['news_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('news_id', 'news', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

    }
}
