<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAuthentification extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users__twofactorauth');

        $table
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('secret', 'string', ['null' => false])
            ->addColumn('enabled', 'boolean', ['null' => false, 'default' => 1])
            ->addTimestamps()
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

    }
}
