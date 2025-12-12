<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNotification extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('notifications');

        $table
            ->addColumn('group', 'string', ['limit' => 10, 'null' => false, 'default' => 'user'])
            ->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
            ->addColumn('from', 'integer', ['null' => true, 'default' => null])
            ->addColumn('content', 'string', ['null' => false])
            ->addColumn('type', 'string', ['limit' => 5, 'null' => false, 'default' => 'user'])
            ->addColumn('seen', 'integer', ['limit' => 1, 'null' => false, 'default' => 0, 'signed' => false])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addIndex(['from'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->addForeignKey('from', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
    }
}
