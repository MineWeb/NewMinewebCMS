<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePage extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('pages', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('title', 'string', ['null' => false, 'limit' => 100])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('slug', 'string', ['null' => false, 'limit' => 150])
            ->addColumn('user_id', 'integer', ['null' => false, 'limit' => 20, 'signed' => false])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
