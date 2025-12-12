<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePermission extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('permissions', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('rank', 'integer', ['null' => false, 'limit' => 1, 'signed' => false])
            ->addColumn('permissions', 'text', ['null' => false])
            ->addTimestamps()
            ->create();
    }
}
