<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateHistory extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('histories', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('action', 'string', ['null' => false])
            ->addColumn('category', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('user_id', 'integer', ['null' => false, 'limit' => 20, 'signed' => false])
            ->addColumn('other', 'text', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
