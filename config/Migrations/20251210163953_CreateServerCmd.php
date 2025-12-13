<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateServerCmd extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('server_cmds');

        $table
            ->addColumn('name', 'string', ['null' => false, 'limit' => 255])
            ->addColumn('server_id', 'integer', ['null' => false])
            ->addColumn('cmd', 'string', ['null' => false, 'limit' => 255])
            ->addTimestamps()
            ->addIndex(['server_id'])
            ->addForeignKey('server_id', 'servers', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

    }
}
