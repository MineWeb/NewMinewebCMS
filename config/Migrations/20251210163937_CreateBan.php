<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBan extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('bans', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('user_id', 'integer', ['null' => false, 'limit' => 20, 'signed' => false])
            ->addColumn('reason', 'text', ['null' => false])
            ->addColumn('ip', 'string', ['null' => true, 'default' => null, 'limit' => 50])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->create();

        $table
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->update();
    }
}
