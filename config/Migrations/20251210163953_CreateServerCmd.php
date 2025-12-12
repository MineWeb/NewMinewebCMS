<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateServerCmd extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('server_cmds', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('name', 'string', ['null' => false, 'limit' => 255])
            ->addColumn('server_id', 'integer', ['null' => false, 'limit' => 8, 'signed' => false])
            ->addColumn('cmd', 'string', ['null' => false, 'limit' => 255])
            ->addTimestamps()
            ->addIndex(['server_id'])
            ->create();

        $table
            ->addForeignKey('server_id', 'servers', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
