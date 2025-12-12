<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMaintenance extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('maintenances', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('sub_url', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('url', 'string', ['null' => false])
            ->addColumn('reason', 'text', ['null' => false])
            ->addColumn('active', 'integer', ['null' => false, 'default' => 1, 'limit' => 1, 'signed' => false])
            ->addTimestamps()
            ->create();
    }
}
