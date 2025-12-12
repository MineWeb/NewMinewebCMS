<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNews extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('news');

        $table
            ->addColumn('title', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('img', 'string', ['null' => false])
            ->addColumn('slug', 'string', ['null' => false, 'limit' => 150])
            ->addColumn('published', 'integer', ['null' => false, 'default' => 1, 'limit' => 1, 'signed' => false])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
