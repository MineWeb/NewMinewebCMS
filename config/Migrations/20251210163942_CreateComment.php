<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateComment extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('comments', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('content', 'text', ['null' => false])
            ->addTimestamps()
            ->addColumn('user_id', 'integer', ['null' => false, 'limit' => 20, 'signed' => false])
            ->addColumn('news_id', 'integer', ['null' => false, 'limit' => 20, 'signed' => false])
            ->addIndex(['user_id'])
            ->addIndex(['news_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('news_id', 'news', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
