<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBan extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('bans');

        $table
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('reason', 'text', ['null' => false])
            ->addColumn('ip', 'string', ['null' => true, 'default' => null, 'limit' => 50])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

    }
}
