<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlugin extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('plugins', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('name', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('author', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('version', 'string', ['null' => false, 'limit' => 20])
            ->addColumn('state', 'integer', ['null' => false, 'default' => 1, 'limit' => 1, 'signed' => false])
            ->addTimestamps()
            ->create();
    }
}
