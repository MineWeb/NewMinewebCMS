<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRank extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('ranks', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('rank_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('name', 'string', ['null' => false, 'limit' => 20])
            ->addTimestamps()
            ->create();
    }
}
