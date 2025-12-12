<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRank extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('ranks');

        $table
            ->addColumn('rank_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('name', 'string', ['null' => false, 'limit' => 20])
            ->addTimestamps()
            ->create();
    }
}
