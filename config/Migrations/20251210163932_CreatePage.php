<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePage extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('pages');

        $table
            ->addColumn('title', 'string', ['null' => false, 'limit' => 100])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('slug', 'string', ['null' => false, 'limit' => 150])
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
