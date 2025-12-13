<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateVisit extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('visits');

        $table
            ->addColumn('ip', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('referer', 'text', ['null' => true, 'default' => null])
            ->addColumn('lang', 'string', ['null' => true, 'default' => 'fr', 'limit' => 4])
            ->addColumn('navigator', 'string', ['null' => true, 'default' => null])
            ->addColumn('page', 'string', ['null' => true, 'default' => null])
            ->addTimestamps()
            ->create();
    }
}
