<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sessions', ['id' => false, 'primary_key' => ['id']]);

        $table
            ->addColumn('id', 'string', ['limit' => 40, 'null' => false])
            ->addColumn('data', 'blob', ['null' => true])
            ->addColumn('expires', 'integer', ['null' => true, 'limit' => 11])
            ->addIndex(['expires'])
            ->create();
    }
}
