<?php

use Phinx\Migration\AbstractMigration;

class CreateServerCmd extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('server_cmds', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('name', 'string', ['null' => false, 'length' => 255])
            ->addColumn('server_id', 'integer', ['null' => false, 'length' => 8, 'signed' => false])
            ->addColumn('cmd', 'string', ['null' => false, 'length' => 255])
            ->addIndex(['server_id'])
            ->create();

        $table
            ->addForeignKey('server_id', 'servers', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
