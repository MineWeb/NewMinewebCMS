<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateServer extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('servers');

        $table
            ->addColumn('name', 'string', ['null' => false, 'limit' => 20])
            ->addColumn('ip', 'string', ['null' => false, 'limit' => 120])
            ->addColumn('port', 'integer', ['null' => false, 'limit' => 5, 'signed' => false])
            ->addColumn('type', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('data', 'string', ['null' => false, 'limit' => 120])
            ->addTimestamps()
            ->create();
    }
}
